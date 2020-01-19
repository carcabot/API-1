<?php

declare(strict_types=1);

namespace App\Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * DQL function to concatenate two or more tsvectors.
 *
 * "tsvector_concat" "(" ArithmeticExpression "," ArithmeticExpression {"," ArithmeticExpression}* ")"
 */
class TsvectorConcatFunction extends FunctionNode
{
    protected $tsvectors;

    /**
     * {@inheritdoc}
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->tsvectors[] = $parser->ArithmeticExpression();

        $parser->match(Lexer::T_COMMA);

        $this->tsvectors[] = $parser->ArithmeticExpression();

        $lexer = $parser->getLexer();
        while ($lexer->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);

            $this->tsvectors[] = $parser->ArithmeticExpression();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * {@inheritdoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return \implode(' || ', \array_map(function ($tsvector) use ($sqlWalker) {
            return $sqlWalker->walkArithmeticExpression($tsvector);
        }, $this->tsvectors));
    }
}
