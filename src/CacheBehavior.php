<?php
namespace dicr\cache;

use yii\base\Behavior;
use yii\base\Event;
use yii\base\InvalidArgumentException;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use yii\di\Instance;

/**
 * Очистка кэша при операциях с моделью.
 *
 * Добавляет модели метод:
 * invalidateModelCache()
 *
 * По событиям изменения модели очищает кэш.
 *
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 2019
 */
class CacheBehavior extends Behavior
{
    /** @var string|\yii\caching\CacheInterface кэш для очистки */
    public $cache = 'cache';

    /**
     * {@inheritDoc}
     * @see \yii\base\BaseObject::init()
     */
    public function init()
    {
        parent::init();

        $this->cache = Instance::ensure($this->cache, CacheInterface::class);
    }

    /**
     * {@inheritDoc}
     * @see \yii\base\Behavior::attach()
     */
    public function attach($owner)
    {
        if (!is_a($owner, ActiveRecord::class, false)) {
            throw new InvalidArgumentException('owner должен быть подклассом ActiveRecord');
        }

        parent::attach($owner);
    }

    /**
     * {@inheritDoc}
     * @see \yii\base\Behavior::events()
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_DELETE => function(Event $event) {
                $this->invalidateModelCache();
            },

            ActiveRecord::EVENT_AFTER_INSERT => function(AfterSaveEvent $event) {
                if (!empty($event->changedAttributes)) {
                    $this->invalidateModelCache();
                }
            },

            ActiveRecord::EVENT_AFTER_UPDATE => function(AfterSaveEvent $event) {
                if (!empty($event->changedAttributes)) {
                    $this->invalidateModelCache();
                }
            }
        ];
    }

    /**
     * Очищает кэш с зависимостями тегов по имени класса модели.
     */
    public function invalidateModelCache()
    {
        TagDependency::invalidate($this->cache, get_class($this->owner));
    }
}