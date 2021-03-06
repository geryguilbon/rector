<?php

declare(strict_types=1);

namespace Rector\Php71\Rector\TryCatch;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\TryCatch;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;
use Rector\Core\ValueObject\PhpVersionFeature;

/**
 * @see https://wiki.php.net/rfc/multiple-catch
 * @see \Rector\Php71\Tests\Rector\TryCatch\MultiExceptionCatchRector\MultiExceptionCatchRectorTest
 */
final class MultiExceptionCatchRector extends AbstractRector
{
    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition(
            'Changes multi catch of same exception to single one | separated.',
            [
                new CodeSample(
<<<'PHP'
try {
   // Some code...
} catch (ExceptionType1 $exception) {
   $sameCode;
} catch (ExceptionType2 $exception) {
   $sameCode;
}
PHP
                    ,
<<<'PHP'
try {
   // Some code...
} catch (ExceptionType1 | ExceptionType2 $exception) {
   $sameCode;
}
PHP
                ),
            ]
        );
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [TryCatch::class];
    }

    /**
     * @param TryCatch $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->isAtLeastPhpVersion(PhpVersionFeature::MULTI_EXCEPTION_CATCH)) {
            return null;
        }

        if (count($node->catches) < 2) {
            return null;
        }

        $catchKeysByContent = $this->collectCatchKeysByContent($node);

        foreach ($catchKeysByContent as $catches) {
            // no duplicates
            if (count($catches) < 2) {
                continue;
            }

            $collectedTypes = $this->collectTypesFromCatchedByIds($node, $catches);

            /** @var Catch_ $firstCatch */
            $firstCatch = array_shift($catches);
            $firstCatch->types = $collectedTypes;

            foreach ($catches as $catch) {
                $this->removeNode($catch);
            }
        }

        return $node;
    }

    /**
     * @return array<string, Catch_[]>
     */
    private function collectCatchKeysByContent(TryCatch $tryCatch): array
    {
        $catchKeysByContent = [];
        foreach ($tryCatch->catches as $catch) {
            $catchContent = $this->print($catch->stmts);
            $catchKeysByContent[$catchContent][] = $catch;
        }

        return $catchKeysByContent;
    }

    /**
     * @param Catch_[] $catches
     * @return Name[]
     */
    private function collectTypesFromCatchedByIds(TryCatch $tryCatch, array $catches): array
    {
        $collectedTypes = [];

        foreach ($catches as $catch) {
            $collectedTypes = array_merge($collectedTypes, $catch->types);
        }

        return $collectedTypes;
    }
}
