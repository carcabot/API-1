<?php

declare(strict_types=1);

namespace App\Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * DQL function to parse text to tsvector.
 *
 * "to_tsvector" "(" [ArithmeticExpression ","] ArithmeticExpression ")"
 */
class ToTsvectorFunction extends FunctionNode
{
    protected $config;
    protected $document;

    /**
     * {@inheritdoc}
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->document = $parser->ArithmeticExpression();

        $lexer = $parser->getLexer();
        if ($lexer->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);

            $this->config = $this->document;
            $this->document = $parser->ArithmeticExpression();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * {@inheritdoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return \sprintf(
            'to_tsvector(%s%s)',
            null !== $this->config ? $sqlWalker->walkArithmeticExpression($this->config).', ' : '',
            $sqlWalker->walkArithmeticExpression($this->document)
        );
    }
}
