<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 22.03.20 00:26:27
 */

declare(strict_types = 1);

namespace dicr\cache;

use yii\base\Behavior;
use yii\base\Event;
use yii\base\InvalidArgumentException;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use yii\di\Instance;
use function get_class;

/**
 * Очистка кэша при операциях с моделью.
 *
 * Добавляет модели метод:
 * invalidateModelCache()
 *
 * По событиям изменения модели очищает кэш.
 *
 * @noinspection PhpUnused
 */
class CacheBehavior extends Behavior
{
    /** @var string|\yii\caching\CacheInterface кэш для очистки */
    public $cache = 'cache';

    /**
     * {@inheritDoc}
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        $this->cache = Instance::ensure($this->cache, CacheInterface::class);
    }

    /**
     * {@inheritDoc}
     */
    public function attach($owner)
    {
        if (! is_a($owner, ActiveRecord::class, false)) {
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
        if ($this->owner) {
            TagDependency::invalidate($this->cache, get_class($this->owner));
        }
    }

    /**
     * Обработчик обновления модели.
     *
     * @param \yii\base\Event $event
     */
    public function _handleModelChange(Event $event)
    {
        // пропускаем если никакие характеристики не обновились
        if (($event instanceof AfterSaveEvent) && empty($event->changedAttributes)) {
            return;
        }

        $this->invalidateModelCache();
    }
}
