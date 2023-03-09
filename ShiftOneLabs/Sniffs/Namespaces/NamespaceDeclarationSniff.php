<?php

namespace ShiftOneLabs\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Ensures namespaces are declared correctly.
 */
class NamespaceDeclarationSniff implements Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_NAMESPACE);
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File  $phpcsFile  The file being scanned.
     * @param int  $stackPtr  The position of the current token in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $stackPrev = $stackPtr - 1;
        $stackNext = $stackPtr + 1;

        // One space after the namespace keyword.
        if ($tokens[$stackNext]['content'] !== ' ') {
            $error = 'There must be a single space after the NAMESPACE keyword';
            $fix = $phpcsFile->addFixableError($error, $stackPtr, 'SpaceAfterNamespace');
            if ($fix === true) {
                $phpcsFile->fixer->replaceToken($stackNext, ' ');
            }
        }

        // Find the previous token that is NOT whitespace
        $lastNonWhitespace = $phpcsFile->findPrevious(T_WHITESPACE, $stackPrev, null, true);

        // should not happen; something is horribly wrong
        if ($lastNonWhitespace === false) {
            return;
        }

        $diff = ($tokens[$stackPtr]['line'] - $tokens[$lastNonWhitespace]['line']);

        if ($diff !== 2) {
            $error = 'There must be one blank line before the namespace declaration';
            $fix = $phpcsFile->addFixableError($error, $stackPtr, 'BlankLineBefore');

            if ($fix === true) {
                /*
                 * If there is less than a 2 line difference, add newlines in.
                 * If there is more than a 2 line difference, take newlines out.
                 */
                if ($diff < 2) {
                    $phpcsFile->fixer->beginChangeset();
                    for ($i = 0; $i < (2 - $diff); $i++) {
                        $phpcsFile->fixer->addNewlineBefore($stackPtr);
                    }
                    $phpcsFile->fixer->endChangeset();
                } else {
                    /*
                     * Remove all whitespace between the namespace and the previous non-whitespace
                     * tokens. If a newline was removed from the same line as the previous
                     * non-whitespace token, we need to make sure to add that back in.
                     */
                    $phpcsFile->fixer->beginChangeset();
                    $newlineRemoved = false;
                    for ($i = ($lastNonWhitespace + 1); $i < $stackPtr; $i++) {
                        if ($tokens[$lastNonWhitespace]['line'] === $tokens[$i]['line']) {
                            $newlineRemoved = true;
                        }
                        $phpcsFile->fixer->replaceToken($i, '');
                    }
                    if ($newlineRemoved) {
                        $phpcsFile->fixer->addNewline($lastNonWhitespace);
                    }
                    $phpcsFile->fixer->addNewline($lastNonWhitespace);
                    $phpcsFile->fixer->endChangeset();
                }
            }
        } elseif ($tokens[$stackPtr]['column'] !== 1) {
            $error = 'Line indented incorrectly; expected 0 spaces, found '.($tokens[$stackPtr]['column'] - 1);
            $fix = $phpcsFile->addFixableError($error, $stackPtr, 'NoSpaceBeforeNamespace');

            if ($fix === true) {
                $content = explode("\n", $tokens[$stackPrev]['content']);
                $content[count($content) - 1] = '';
                $phpcsFile->fixer->replaceToken($stackPrev, implode("\n", $content));
            }
        }
    }
}
