<?php

namespace Larasell\Admin\Lint\PHPStan;

use Illuminate\Testing\TestResponse;
use Pest\PendingCalls\TestCall;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPUnit\Framework\TestCase;

/**
 * Tests should build URLs with route() rather than hard-coding paths like '/admin/login',
 * so they follow the route definitions and the configurable admin path.
 *
 * @implements Rule<Expr\CallLike>
 */
final class PreferRouteHelperRule implements Rule
{
    /** Methods on test cases and responses that take a URL, with the index of that argument. */
    private const METHOD_URL_ARGUMENTS = [
        'get' => 0, 'getjson' => 0,
        'post' => 0, 'postjson' => 0,
        'put' => 0, 'putjson' => 0,
        'patch' => 0, 'patchjson' => 0,
        'delete' => 0, 'deletejson' => 0,
        'options' => 0, 'optionsjson' => 0,
        'head' => 0,
        'json' => 1,
        'call' => 1,
        'from' => 0,
        'assertredirect' => 0,
        'assertlocation' => 0,
    ];

    public function __construct(private Printer $printer) {}

    public function getNodeType(): string
    {
        return Expr\CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $url = $this->urlArgument($node, $scope);
        if ($url === null) {
            return [];
        }

        $path = $this->leadingString($url, $scope);
        if ($path === null || ! str_starts_with($path, '/')) {
            return [];
        }

        $shown = $url instanceof InterpolatedString || $url instanceof Concat
            ? $this->printer->prettyPrintExpr($url)
            : "'{$path}'";

        return [
            RuleErrorBuilder::message("Use route() instead of the hard-coded URL {$shown}.")
                ->identifier('larasell.preferRouteHelper')
                ->line($url->getStartLine())
                ->tip('If the path has no named route on purpose, such as a 404 check, add // @phpstan-ignore larasell.preferRouteHelper')
                ->build(),
        ];
    }

    private function urlArgument(Expr\CallLike $node, Scope $scope): ?Expr
    {
        if ($node->isFirstClassCallable()) {
            return null;
        }

        if ($node instanceof FuncCall) {
            return $node->name instanceof Name && $node->name->toLowerString() === 'url'
                ? $node->getArgs()[0]->value ?? null
                : null;
        }

        if (! $node instanceof MethodCall || ! $node->name instanceof Node\Identifier) {
            return null;
        }

        $index = self::METHOD_URL_ARGUMENTS[$node->name->toLowerString()] ?? null;
        if ($index === null || ! $this->isTestReceiver($scope->getType($node->var))) {
            return null;
        }

        return $node->getArgs()[$index]->value ?? null;
    }

    /**
     * Skips receivers known to be something else, like a Collection. Pest binds $this to a
     * TestCall in PHPStan's eyes, and calls chained on it resolve to an unknown type.
     */
    private function isTestReceiver(Type $type): bool
    {
        if (! $type->isObject()->yes()) {
            return true;
        }

        foreach ([TestCall::class, TestCase::class, TestResponse::class] as $class) {
            if ((new ObjectType($class))->isSuperTypeOf($type)->yes()) {
                return true;
            }
        }

        return false;
    }

    /** The literal text a URL starts with: '/admin/categories/' for "/admin/categories/{$id}". */
    private function leadingString(Expr $expr, Scope $scope): ?string
    {
        if ($expr instanceof String_) {
            return $expr->value;
        }

        if ($expr instanceof Concat) {
            return $this->leadingString($expr->left, $scope);
        }

        if ($expr instanceof InterpolatedString) {
            $first = $expr->parts[0] ?? null;

            return $first instanceof InterpolatedStringPart ? $first->value : null;
        }

        $strings = $scope->getType($expr)->getConstantStrings();

        return count($strings) === 1 ? $strings[0]->getValue() : null;
    }
}
