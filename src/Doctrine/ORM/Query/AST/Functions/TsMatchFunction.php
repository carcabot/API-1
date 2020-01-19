<?php

declare(strict_types=1);

namespace App\Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * DQL function to perform text search match.
 *
 * "ts_match" "(" ArithmeticExpression "," ArithmeticExpression ")"
 */
class TsMatchFunction extends FunctionNode
{
    protected $tsvector;
    protected $tsquery;

    /**
     * {@inheritdoc}
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->tsvector = $parser->ArithmeticExpression();

        $parser->match(Lexer::T_COMMA);

        $this->tsquery = $parser->ArithmeticExpression();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * {@inheritdoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return \sprintf(
            '%s @@ %s',
            $sqlWalker->walkArithmeticExpression($this->tsvector),
            $sqlWalker->walkArithmeticExpression($this->tsquery)
        );
    }
}
