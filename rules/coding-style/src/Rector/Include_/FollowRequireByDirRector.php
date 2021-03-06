<?php

declare(strict_types=1);

namespace Rector\CodingStyle\Rector\Include_;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\String_;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;

/**
 * @see \Rector\CodingStyle\Tests\Rector\Include_\FollowRequireByDirRector\FollowRequireByDirRectorTest
 */
final class FollowRequireByDirRector extends AbstractRector
{
    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('include/require should be followed by absolute path', [
            new CodeSample(
                <<<'PHP'
class SomeClass
{
    public function run()
    {
        require 'autoload.php';
    }
}
PHP
                ,
                <<<'PHP'
class SomeClass
{
    public function run()
    {
        require __DIR__ . '/autoload.php';
    }
}
PHP
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Include_::class];
    }

    /**
     * @param Include_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->expr instanceof Concat && $node->expr->left instanceof String_) {
            $node->expr->left = $this->prefixWithDir($node->expr->left);

            return $node;
        }

        if ($node->expr instanceof String_) {
            $node->expr = $this->prefixWithDir($node->expr);

            return $node;
        }
        // nothing we can do
        return null;
    }

    private function prefixWithDir(String_ $node): ?Node
    {
        if (Strings::startsWith($node->value, 'phar://')) {
            return $node;
        }

        $this->removeExtraDotSlash($node);
        $this->prependSlashIfMissing($node);

        return new Concat(new Dir(), $node);
    }

    /**
     * Remove "./" which would break the path
     */
    private function removeExtraDotSlash(String_ $string): void
    {
        if (! Strings::startsWith($string->value, './')) {
            return;
        }

        $string->value = Strings::replace($string->value, '#^\.\/#', '/');
    }

    private function prependSlashIfMissing(String_ $string): void
    {
        if (Strings::startsWith($string->value, '/')) {
            return;
        }

        $string->value = '/' . $string->value;
    }
}
