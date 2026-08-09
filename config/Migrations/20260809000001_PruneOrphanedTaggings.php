<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Removes tags_tagged rows whose tag_id no longer resolves to a tags_tags row.
 *
 * TagsTable declared no association to Tagged, and tags_tagged.tag_id carries no
 * foreign key, so every tag deleted before that was corrected left its taggings
 * behind. The rows are unreachable -- each one names a tag that no longer exists,
 * and every query that resolves a tagging joins tags_tags, so they are absent
 * from counts while still occupying the unique index on
 * (tag_id, fk_id, fk_model).
 *
 */
final class PruneOrphanedTaggings extends AbstractMigration
{
    public function up(): void
    {
        $orphans = $this->fetchRow(
            'SELECT COUNT(*) AS c FROM tags_tagged tg
             WHERE NOT EXISTS (SELECT 1 FROM tags_tags t WHERE t.id = tg.tag_id)'
        );
        $count = (int)($orphans['c'] ?? 0);

        if ($count === 0) {
            return;
        }

        $this->execute(
            'DELETE tg FROM tags_tagged tg
             WHERE NOT EXISTS (SELECT 1 FROM tags_tags t WHERE t.id = tg.tag_id)'
        );

        // tags_tags.counter is maintained by the CounterCache attached in
        // TagBehavior::attachCounters(), which a raw DELETE bypasses. Any tag
        // whose count was inflated by these rows needs correcting -- and the
        // same drift may predate this migration, so rebuild all of them.
        $this->execute(
            'UPDATE tags_tags t
             SET t.counter = (SELECT COUNT(*) FROM tags_tagged tg WHERE tg.tag_id = t.id)'
        );
    }

    /**
     * Not reversible: the deleted rows referenced tags that no longer exist, so
     * there is nothing to restore them to.
     */
    public function down(): void
    {
    }
}
