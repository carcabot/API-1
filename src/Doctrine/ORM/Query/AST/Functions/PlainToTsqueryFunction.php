<?php

declare(strict_types=1);

namespace App\Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * DQL function to parse text to tsquery, ignoring punctuation.
 *
 * "plainto_tsquery" "(" [ArithmeticExpression ","] ArithmeticExpression ")"
 */
class PlainToTsqueryFunction extends FunctionNode
{
    protected $config;
    protected $query;

    /**
     * {@inheritdoc}
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->query = $parser->ArithmeticExpression();

        $lexer = $parser->getLexer();
        if ($lexer->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);

            $this->config = $this->query;
            $this->query = $parser->ArithmeticExpression();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * {@inheritdoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return \sprintf(
            'plainto_tsquery(%s%s)',
            null !== $this->config ? $sqlWalker->walkArithmeticExpression($this->config).', ' : '',
            $sqlWalker->walkArithmeticExpression($this->query)
        );
    }
}
