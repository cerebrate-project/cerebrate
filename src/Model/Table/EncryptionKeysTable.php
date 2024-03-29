<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use ArrayObject;

class EncryptionKeysTable extends AppTable
{

    public $gpg = null;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->addBehavior('AuditLog');
        $this->addBehavior('Timestamp');
        $this->belongsTo(
            'Individuals',
            [
                'foreignKey' => 'owner_id',
                'conditions' => ['owner_model' => 'individual']
            ]
        );
        $this->belongsTo(
            'Organisations',
            [
                'foreignKey' => 'owner_id',
                'conditions' => ['owner_model' => 'organisation']
            ]
        );
        $this->setDisplayField('encryption_key');
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        if (empty($data['owner_id'])) {
            if (empty($data['owner_model'])) {
                return false;
            }
            if (empty($data[$data['owner_model'] . '_id'])) {
                return false;
            }
            $data['owner_id'] = $data[$data['owner_model'] . '_id'];
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('type')
            ->notEmptyString('encryption_key')
            ->notEmptyString('owner_id')
            ->notEmptyString('owner_model')
            ->requirePresence(['type', 'encryption_key', 'owner_id', 'owner_model'], 'create');
        return $validator;
    }

    /**
     * 0 - true if key is valid
     * 1 - User e-mail
     * 2 - Error message
     * 3 - Not used
     * 4 - Key fingerprint
     * 5 - Key fingerprint
     * @param \App\Model\Entity\EncryptionKey $encryptionKey
     * @return array
     */
    public function verifySingleGPG(\App\Model\Entity\EncryptionKey $encryptionKey): array
    {
        $result = [0 => false, 1 => null];

        $gpg = $this->initializeGpg();
        if (!$gpg) {
            $result[2] = 'GnuPG is not configured on this system.';
            return $result;
        }

        try {
            $currentTimestamp = time();
            $keys = $gpg->keyInfo($encryptionKey['encryption_key']);
            if (count($keys) !== 1) {
                $result[2] = 'Multiple or no key found';
                return $result;
            }

            $key = $keys[0];
            $result[4] = $key->getPrimaryKey()->getFingerprint();
            $result[5] = $result[4];

            $sortedKeys = ['valid' => 0, 'expired' => 0, 'noEncrypt' => 0];
            foreach ($key->getSubKeys() as $subKey) {
                $expiration = $subKey->getExpirationDate();
                if ($expiration != 0 && $currentTimestamp > $expiration) {
                    $sortedKeys['expired']++;
                    continue;
                }
                if (!$subKey->canEncrypt()) {
                    $sortedKeys['noEncrypt']++;
                    continue;
                }
                $sortedKeys['valid']++;
            }
            if (!$sortedKeys['valid']) {
                $result[2] = 'The user\'s PGP key does not include a valid subkey that could be used for encryption.';
                if ($sortedKeys['expired']) {
                    $result[2] .= ' ' . __n('Found %s subkey that have expired.', 'Found %s subkeys that have expired.', $sortedKeys['expired'], $sortedKeys['expired']);
                }
                if ($sortedKeys['noEncrypt']) {
                    $result[2] .= ' ' . __n('Found %s subkey that is sign only.', 'Found %s subkeys that are sign only.', $sortedKeys['noEncrypt'], $sortedKeys['noEncrypt']);
                }
            } else {
                $result[0] = true;
            }
        } catch (\Exception $e) {
            $result[2] = $e->getMessage();
        }
        return $result;
    }


    /**
     * Initialize GPG. Returns `null` if initialization failed.
     *
     * @return null|CryptGpgExtended
     */
    public function initializeGpg()
    {
        require_once(ROOT . '/src/Lib/Tools/GpgTool.php');
        if ($this->gpg !== null) {
            if ($this->gpg === false) { // initialization failed
                return null;
            }
            return $this->gpg;
        }

        try {
            $this->gpg = \App\Lib\Tools\GpgTool::initializeGpg();
            return $this->gpg;
        } catch (\Exception $e) {
            //$this->logException("GPG couldn't be initialized, GPG encryption and signing will be not available.", $e, LOG_NOTICE);
            $this->gpg = false;
            return null;
        }
    }

    public function canEdit($user, $entity): bool
    {
        if ($entity['owner_model'] === 'organisation') {
            return $this->canEditForOrganisation($user, $entity);
        } else if ($entity['owner_model'] === 'individual') {
            return $this->canEditForIndividual($user, $entity);
        }
        return false;
    }

    public function canEditForOrganisation($user, $entity): bool
    {
        if ($entity['owner_model'] !== 'organisation') {
            return false;
        }
        if (!empty($user['role']['perm_admin'])) {
            return true;
        }
        if (
            $user['role']['perm_org_admin'] && 
            $entity['owner_id'] === $user['organisation_id']
        ) {
            return true;
        }
        return false;
    }

    public function canEditForIndividual($user, $entity): bool
    {
        if ($entity['owner_model'] !== 'individual') {
            return false;
        }
        if (!empty($user['role']['perm_admin'])) {
            return true;
        }
        if ($user['role']['perm_org_admin']) {
            $this->Alignments = TableRegistry::get('Alignments');
            $validIndividuals = $this->Alignments->find('list', [
                'keyField' => 'individual_id',
                'valueField' => 'id',
                'conditions' => ['organisation_id' => $user['organisation_id']]
            ])->toArray();
            if (isset($validIndividuals[$entity['owner_id']])) {
                return true;
            }
        } else {
            if ($entity['owner_id'] === $user['individual_id']) {
                return true;
            }
        }
        return false;
    }
}
