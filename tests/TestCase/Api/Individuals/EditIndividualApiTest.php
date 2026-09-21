<?php

declare(strict_types=1);

namespace App\Test\TestCase\Api\Individuals;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use App\Test\Fixture\AuthKeysFixture;
use App\Test\Fixture\IndividualsFixture;
use App\Test\Helper\ApiTestTrait;

class EditIndividualApiTest extends TestCase
{
    use ApiTestTrait;

    protected const ENDPOINT = '/individuals/edit';

    protected $fixtures = [
        'app.Organisations',
        'app.Individuals',
        'app.Roles',
        'app.Users',
        'app.AuthKeys'
    ];

    public function testEditIndividualAsAdmin(): void
    {
        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);
        $url = sprintf('%s/%d', self::ENDPOINT, IndividualsFixture::INDIVIDUAL_REGULAR_USER_ID);
        $this->put(
            $url,
            [
                'email' => 'foo@bar.com',
            ]
        );

        $this->assertResponseOk();
        $this->assertDbRecordExists('Individuals', [
            'id' => IndividualsFixture::INDIVIDUAL_REGULAR_USER_ID,
            'email' => 'foo@bar.com'
        ]);
    }

    /**
     * The row that gets written must be the one addressed by the URL, never one named in the
     * body. Table::_update() takes its WHERE clause verbatim from the entity's primary key, so
     * a PK that drifts between load and save writes to the wrong row - or, when it lands on
     * 0/null, to no row at all while save() still reports success.
     */
    public function testEditCannotBeRedirectedToAnotherIndividualViaBodyId(): void
    {
        $target = IndividualsFixture::INDIVIDUAL_REGULAR_USER_ID;
        $victim = IndividualsFixture::INDIVIDUAL_ADMIN_ID;
        $individuals = TableRegistry::getTableLocator()->get('Individuals');
        $victimEmailBefore = $individuals->get($victim)->email;

        $this->setAuthToken(AuthKeysFixture::ADMIN_API_KEY);
        $this->put(
            sprintf('%s/%d', self::ENDPOINT, $target),
            [
                'id' => $victim,
                'email' => 'foo@bar.com',
            ]
        );

        $this->assertResponseOk();
        $this->assertDbRecordExists('Individuals', ['id' => $target, 'email' => 'foo@bar.com']);
        $this->assertDbRecordExists('Individuals', ['id' => $victim, 'email' => $victimEmailBefore]);
    }

    public function testEditAnyIndividualNotAllowedAsRegularUser(): void
    {
        $this->setAuthToken(AuthKeysFixture::REGULAR_USER_API_KEY);
        $url = sprintf('%s/%d', self::ENDPOINT, IndividualsFixture::INDIVIDUAL_ADMIN_ID);
        $this->put(
            $url,
            [
                'email' => 'foo@bar.com',
            ]
        );

        $this->assertResponseCode(405);
        $this->assertDbRecordNotExists('Individuals', [
            'id' => IndividualsFixture::INDIVIDUAL_ADMIN_ID,
            'email' => 'foo@bar.com'
        ]);
    }
}
