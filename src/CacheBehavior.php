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
            ActiveRecord::EVENT_AFTER_INSERT => '_handleModelChange',
            ActiveRecord::EVENT_AFTER_UPDATE => '_handleModelChange',
            ActiveRecord::EVENT_AFTER_DELETE => '_handleModelChange',
        ];
    }

    /**
     * Очищает кэш с зависимостями тегов по имени класса модели.
     */
    public function invalidateModelCache()
    {
        TagDependency::invalidate($this->cache, get_class($this->owner));
    }

    /**
     * Обработчик обновления модели.
     *
     * @param \yii\db\AfterSaveEvent $event
     */
    public function _handleModelChange(Event $event)
    {
        // пропускаем если никакие характрисики не обновились
        if (($event instanceof AfterSaveEvent) && empty($event->changedAttributes)) {
            return;
        }

        $this->invalidateModelCache();
    }
}