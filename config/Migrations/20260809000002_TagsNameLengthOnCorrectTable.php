<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Applies the `name` widening that (I think) 20250829000000_GinormousTags 
 * intended.
 *
 * That migration operated on a table called `tags`, but the Tags plugin's table
 * is `tags_tags` (TagsTable::initialize() calls setTable('tags_tags'), and
 * plugins/Tags/config/Migrations/20210831121348_TagSystem.php creates it). No
 * migration anywhere creates a bare `tags` table, so two things happened:
 *
 *   1. tags_tags.name was never widened and remains varchar(191);
 *   2. an empty `tags` table was created as a side effect, because Phinx's
 *      ->save() on a non-existent table creates it.
 *
 * This cannot be fixed by editing the original, which has already run on every
 * deployment and will not run again.
 *
 * Note the index on tags_tags.name is UNIQUE -- TagSystem creates it with
 * ['unique' => true], and getExistingTag() plus every "create tag if absent"
 * path depends on that. The original migration re-added the index without
 * `unique`, which was harmless only because it was acting on an empty table
 * nothing reads. Applying it verbatim to tags_tags would have dropped the
 * constraint.
 *
 * The index keeps a 191-byte prefix, as the original intended. utf8mb4 at 255
 * characters is 1020 bytes, which exceeds the 767-byte index limit on older
 * InnoDB row formats. Uniqueness is therefore enforced on the first 191
 * characters rather than the full name.
 */
final class TagsNameLengthOnCorrectTable extends AbstractMigration
{
    public function up(): void
    {
        $tagsTags = $this->table('tags_tags');

        if ($tagsTags->hasIndex('name')) {
            $tagsTags->removeIndex('name')->save();
        }

        $tagsTags->changeColumn('name', 'string', [
            'limit' => 255,
            'null' => false,
        ])->update();

        $tagsTags->addIndex('name', ['unique' => true, 'limit' => 191])->save();

        // Drop the empty table the original migration created by mistake. It is
        // empty by construction: nothing reads or writes it, so any row would
        // have to have been inserted by hand. Guarded rather than assumed.
        if ($this->hasTable('tags')) {
            $row = $this->fetchRow('SELECT COUNT(*) AS c FROM tags');
            if ((int)($row['c'] ?? 0) === 0) {
                $this->table('tags')->drop()->save();
            }
        }
    }

    public function down(): void
    {
        $row = $this->fetchRow(
            'SELECT COALESCE(MAX(CHAR_LENGTH(name)), 0) AS longest FROM tags_tags'
        );

        // Narrowing would truncate, or fail outright under STRICT_TRANS_TABLES.
        // Leave the column alone rather than lose tag names on a rollback.
        if ((int)($row['longest'] ?? 0) > 191) {
            return;
        }

        $tagsTags = $this->table('tags_tags');

        if ($tagsTags->hasIndex('name')) {
            $tagsTags->removeIndex('name')->save();
        }

        $tagsTags->changeColumn('name', 'string', [
            'limit' => 191,
            'null' => false,
        ])->update();

        $tagsTags->addIndex('name', ['unique' => true])->save();
    }
}
