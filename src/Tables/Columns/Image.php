<?php

namespace Larasell\Admin\Tables\Columns;

use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use JsonSerializable;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\Table\ImageStyle;
use Larasell\Admin\Exceptions\InvalidTableColumn;

/**
 * The image a column shows before its value in one row: an icon or a picture
 * at a url. Column::image() makes one for each row and passes it to its callback.
 */
class Image implements JsonSerializable
{
    protected ?Icon $icon = null;

    protected ?string $url = null;

    /** Null until rounded() or style() is called; pictures are square by default, and icons have no style. */
    protected ?ImageStyle $style = null;

    protected ?string $alt = null;

    protected ?string $title = null;

    /** @var array<int, string> The configuration methods called so far, which may each be called only once. */
    private array $configured = [];

    /**
     * @param  string  $column  The name of the column the image belongs to, for error messages.
     */
    final public function __construct(protected string $column) {}

    /**
     * Show an icon: an Icon case or its Remix icon name, e.g. "book-open".
     */
    public function icon(Icon|string $icon): static
    {
        $this->configureOnce('icon(), url() or a route method');
        $this->icon = is_string($icon) ? $this->resolveIcon($icon) : $icon;

        return $this;
    }

    /**
     * Show the picture at the url.
     */
    public function url(string $url): static
    {
        $this->configureOnce('icon(), url() or a route method');

        if (Str::trim($url) === '') {
            throw InvalidTableColumn::emptyImageUrl($this->column);
        }

        $this->url = $url;

        return $this;
    }

    /**
     * Alias of url().
     */
    public function to(string $url): static
    {
        return $this->url($url);
    }

    /**
     * Show the picture at a named route, like URL::route().
     */
    public function route(string $name, mixed $parameters = [], bool $absolute = true): static
    {
        return $this->url(URL::route($name, $parameters, $absolute));
    }

    /**
     * Show the picture at a signed url of a named route, like URL::signedRoute().
     */
    public function signedRoute(string $name, mixed $parameters = [], DateTimeInterface|DateInterval|int|null $expiration = null, bool $absolute = true): static
    {
        return $this->url(URL::signedRoute($name, $parameters, $expiration, $absolute));
    }

    /**
     * Show the picture at a signed url of a named route that expires, like URL::temporarySignedRoute().
     */
    public function temporarySignedRoute(string $name, DateTimeInterface|DateInterval|int $expiration, mixed $parameters = [], bool $absolute = true): static
    {
        return $this->url(URL::temporarySignedRoute($name, $expiration, $parameters, $absolute));
    }

    /**
     * Show the picture fully rounded, e.g. for an avatar. Same as style(ImageStyle::Round).
     */
    public function rounded(): static
    {
        return $this->style(ImageStyle::Round);
    }

    /**
     * Show the picture round or square. Pictures are square unless this or rounded() says otherwise.
     * Icons have no style: they show as they are, without a background.
     */
    public function style(ImageStyle $style): static
    {
        $this->configureOnce('rounded() or style()');
        $this->style = $style;

        return $this;
    }

    /**
     * Name the image for screen readers, e.g. the person an avatar shows. Leave
     * it out when the image only decorates the column's value, which already says the same.
     */
    public function alt(string $alt): static
    {
        $this->configureOnce('alt()');
        $this->alt = $alt;

        return $this;
    }

    /**
     * Show a tooltip when hovering the image.
     */
    public function title(string $title): static
    {
        $this->configureOnce('title()');
        $this->title = $title;

        return $this;
    }

    /**
     * The icon is sent as its case name, which @larasell-dev/ui/icons exports with a Fill or Line suffix.
     *
     * Only pictures have a style. The alt text and title are only sent when set.
     *
     * @return array{type: 'icon'|'url', icon?: string, url?: string, style?: string, alt?: string, title?: string}
     */
    public function jsonSerialize(): array
    {
        if ($this->icon !== null && $this->style !== null) {
            throw InvalidTableColumn::styledImageIcon($this->column);
        }

        $source = match (true) {
            $this->icon !== null => ['type' => 'icon', 'icon' => $this->icon->name],
            $this->url !== null => ['type' => 'url', 'url' => $this->url, 'style' => ($this->style ?? ImageStyle::Square)->value],
            default => throw InvalidTableColumn::imageWithoutSource($this->column),
        };

        return [
            ...$source,
            ...$this->alt === null ? [] : ['alt' => $this->alt],
            ...$this->title === null ? [] : ['title' => $this->title],
        ];
    }

    private function configureOnce(string $method): void
    {
        if (in_array($method, $this->configured, true)) {
            throw InvalidTableColumn::imageConfiguredTwice($this->column, $method);
        }

        $this->configured[] = $method;
    }

    protected function resolveIcon(string $icon): Icon
    {
        if (Str::trim($icon) === '') {
            throw InvalidTableColumn::emptyImageIcon($this->column);
        }

        // Checked before the lookup: "home-line" is not a case either, but this error says how to fix it.
        if (Str::endsWith($icon, ['-line', '-fill'])) {
            throw InvalidTableColumn::imageIconStyleVariant($this->column, $icon);
        }

        return Icon::tryFrom($icon) ?? throw InvalidTableColumn::unknownImageIcon($this->column, $icon);
    }
}
