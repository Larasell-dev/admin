<?php

namespace Larasell\Admin\Lint\PHPStan;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Every Pest test marks what it sets up, what it exercises and what it checks with
 * // Arrange, // Act and // Assert comments, in that order. Sections that can't be told apart
 * share one comment, such as // Act & Assert, and a test that sets nothing up has no // Arrange.
 *
 * @implements Rule<FuncCall>
 */
final class ArrangeActAssertRule implements Rule
{
    private const MARKER = '~^//\s*(?:Arrange|Act|Assert)(?: & (?:Arrange|Act|Assert))*\s*$~';

    private const TIP = 'Leave out // Arrange when the test sets nothing up, and combine sections that can\'t be split, such as // Act & Assert.';

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Name || ! in_array($node->name->toLowerString(), ['it', 'test'], true)) {
            return [];
        }

        $callback = $node->getArgs()[1]->value ?? null;
        if (! $callback instanceof Closure && ! $callback instanceof ArrowFunction) {
            return [];
        }

        $markers = $this->markers($callback);
        $sections = array_merge(...array_map(fn (Comment $marker) => $this->sections($marker), $markers));

        if ($sections !== ['Arrange', 'Act', 'Assert'] && $sections !== ['Act', 'Assert']) {
            $found = $markers === []
                ? 'none'
                : implode(', ', array_map(fn (Comment $marker) => $marker->getText(), $markers));

            return [
                RuleErrorBuilder::message("Mark this test's sections with // Arrange, // Act and // Assert, in that order. Found: {$found}.")
                    ->identifier('larasell.arrangeActAssert')
                    ->line($callback->getStartLine())
                    ->tip(self::TIP)
                    ->build(),
            ];
        }

        $first = $callback instanceof Closure ? $callback->stmts[0] ?? null : $callback->expr;
        if ($first !== null && $first->getStartLine() < $markers[0]->getStartLine()) {
            return [
                RuleErrorBuilder::message("Start this test with its {$markers[0]->getText()} comment: the code above it belongs to no section.")
                    ->identifier('larasell.arrangeActAssert')
                    ->line($first->getStartLine())
                    ->build(),
            ];
        }

        return [];
    }

    /**
     * The marker comments in the test, in the order they appear.
     *
     * @return list<Comment>
     */
    private function markers(Closure|ArrowFunction $callback): array
    {
        $markers = [];
        $body = $callback instanceof Closure ? $callback->stmts : [$callback->expr];
        foreach ((new NodeFinder)->find($body, fn () => true) as $inner) {
            foreach ($inner->getComments() as $comment) {
                if (preg_match(self::MARKER, $comment->getText()) === 1) {
                    $markers[$comment->getStartFilePos()] = $comment;
                }
            }
        }
        ksort($markers);

        return array_values($markers);
    }

    /** @return list<string> */
    private function sections(Comment $marker): array
    {
        return explode(' & ', trim(ltrim($marker->getText(), '/')));
    }
}
