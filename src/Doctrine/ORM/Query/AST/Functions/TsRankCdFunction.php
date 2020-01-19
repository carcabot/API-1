<?php

declare(strict_types=1);

namespace App\Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * DQL function to rank text search results using cover density.
 *
 * "ts_rank_cd" "(" [ArithmeticExpression ","] ArithmeticExpression "," ArithmeticExpression ["," ArithmeticExpression] ")"
 */
class TsRankCdFunction extends FunctionNode
{
    protected $expressions = [];

    /**
     * {@inheritdoc}
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->expressions[] = $parser->ArithmeticExpression();

        $parser->match(Lexer::T_COMMA);

        $this->expressions[] = $parser->ArithmeticExpression();

        $lexer = $parser->getLexer();

        if ($lexer->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);

            $this->expressions[] = $parser->ArithmeticExpression();
        }

        if ($lexer->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);

            $this->expressions[] = $parser->ArithmeticExpression();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * {@inheritdoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return \sprintf(
            'ts_rank_cd(%s)',
            \implode(', ', \array_map(function ($expression) use ($sqlWalker) {
                return $sqlWalker->walkArithmeticExpression($expression);
            }, $this->expressions))
        );
    }
}
