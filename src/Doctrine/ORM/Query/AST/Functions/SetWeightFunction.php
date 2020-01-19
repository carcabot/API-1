<?php

declare(strict_types=1);

namespace App\Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * DQL function to assign weight to elements of a tsvector.
 *
 * "setweight" "(" ArithmeticExpression "," ArithmeticExpression ["," ArithmeticExpression] ")"
 */
class SetWeightFunction extends FunctionNode
{
    protected $vector;
    protected $weight;
    protected $lexemes;

    /**
     * {@inheritdoc}
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->vector = $parser->ArithmeticExpression();

        $parser->match(Lexer::T_COMMA);

        $this->weight = $parser->ArithmeticExpression();

        $lexer = $parser->getLexer();
        if ($lexer->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);

            $this->lexemes = $parser->ArithmeticExpression();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * {@inheritdoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return \sprintf(
            'setweight(%s, %s%s)',
            $sqlWalker->walkArithmeticExpression($this->vector),
            $sqlWalker->walkArithmeticExpression($this->weight),
            null !== $this->lexemes ? ', '.$sqlWalker->walkArithmeticExpression($this->lexemes) : ''
        );
    }
}
