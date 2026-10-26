<?php

declare(strict_types=1);

namespace Atannex\Detector\Data;

use Atannex\Detector\Enums\BotCategory;
use JsonSerializable;

final class Bot implements JsonSerializable
{
    public function __construct(
        public readonly string $name,
        public readonly BotCategory $category = BotCategory::Other,
        public readonly ?string $producer = null,
        public readonly ?string $url = null,
    ) {}

    public function is(string ...$names): bool
    {
        foreach ($names as $name) {
            if (strcasecmp($this->name, $name) === 0 || ($this->producer !== null && strcasecmp($this->producer, $name) === 0)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  BotCategory|string  ...$categories  'ai' stands for every AI category
     */
    public function in(BotCategory|string ...$categories): bool
    {
        foreach ($categories as $category) {
            if ($category === 'ai' ? $this->category->isAi() : $this->category === ($category instanceof BotCategory ? $category : BotCategory::fromName($category))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{name: string, category: string, producer: ?string, url: ?string}
     */
    public function jsonSerialize(): array
    {
        return ['name' => $this->name, 'category' => $this->category->value, 'producer' => $this->producer, 'url' => $this->url];
    }
}
