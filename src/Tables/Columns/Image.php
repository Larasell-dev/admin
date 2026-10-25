<?php

namespace Larasell\Admin\Tables\Columns;

use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Exceptions\InvalidTableColumn;

/**
 * Chooses what a column shows before its value in one row. Column::image()
 * makes one for each row and passes it to its callback, which returns the
 * IconImage or PictureImage it makes. Only a PictureImage has a style, so a
 * style on an icon is an undefined method that IDEs and PHPStan flag.
 */
class Image
{
    /** Whether an image was made already: the callback can return only one, so a second would silently not show. */
    private bool $made = false;

    /**
     * @param  string  $column  The name of the column the image belongs to, for error messages.
     */
    final public function __construct(protected string $column) {}

    /**
     * Show an icon: an Icon case or its Remix icon name, e.g. "book-open".
     */
    public function icon(Icon|string $icon): IconImage
    {
        $this->makeOnce();

        return new IconImage($this->column, is_string($icon) ? $this->resolveIcon($icon) : $icon);
    }

    /**
     * Show the picture at the url.
     */
    public function url(string $url): PictureImage
    {
        $this->makeOnce();

        return new PictureImage($this->column, $url);
    }

    /**
     * Alias of url().
     */
    public function to(string $url): PictureImage
    {
        return $this->url($url);
    }

    /**
     * Show the picture at a named route, like URL::route().
     */
    public function route(string $name, mixed $parameters = [], bool $absolute = true): PictureImage
    {
        return $this->url(URL::route($name, $parameters, $absolute));
    }

    /**
     * Show the picture at a signed url of a named route, like URL::signedRoute().
     */
    public function signedRoute(string $name, mixed $parameters = [], DateTimeInterface|DateInterval|int|null $expiration = null, bool $absolute = true): PictureImage
    {
        return $this->url(URL::signedRoute($name, $parameters, $expiration, $absolute));
    }

    /**
     * Show the picture at a signed url of a named route that expires, like URL::temporarySignedRoute().
     */
    public function temporarySignedRoute(string $name, DateTimeInterface|DateInterval|int $expiration, mixed $parameters = [], bool $absolute = true): PictureImage
    {
        return $this->url(URL::temporarySignedRoute($name, $expiration, $parameters, $absolute));
    }

    private function makeOnce(): void
    {
        if ($this->made) {
            throw InvalidTableColumn::imageConfiguredTwice($this->column, 'icon(), url() or a route method');
        }

        $this->made = true;
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
