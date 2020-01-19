<?php

declare(strict_types=1);

namespace App\Repository;

use iter;

trait KeywordSearchRepositoryTrait
{
    /**
     * Gets the aggregated tsquery string from plain keywords.
     *
     * @param string[] $plainKeywords
     * @param bool     $prefixMatching
     *
     * @return string
     */
    public function getKeywordTsquery(array $plainKeywords, bool $prefixMatching = false): string
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        $selectKeywordTsquerySql = \sprintf(<<<'SQL'
            SELECT plainto_tsquery('english', plain_keyword)::text
            FROM (
                VALUES %s
            ) AS t (plain_keyword)
SQL
        , iter\join(', ', iter\map(function ($i) {
            return \sprintf(<<<'SQL'
                (:plain_keyword_%d)
SQL
            , $i);
        }, iter\range(1, \count($plainKeywords)))));

        $selectKeywordTsqueryStmt = $conn->prepare($selectKeywordTsquerySql);

        foreach ($plainKeywords as $i => $plainKeyword) {
            $selectKeywordTsqueryStmt->bindValue(\sprintf('plain_keyword_%d', $i + 1), $plainKeyword);
        }

        $selectKeywordTsqueryStmt->execute();

        $keywordTsqueries = iter\toArray(iter\filter(function ($keywordTsquery) {
            return null !== $keywordTsquery;
        }, iter\map(function ($row) use ($prefixMatching) {
            $tsquery = \reset($row);

            if ('' === $tsquery) {
                return null;
            }

            $lexemes = \preg_split('/\\s*&\\s*/', $tsquery);

            return iter\join(' & ', iter\map(function ($lexeme) use ($prefixMatching) {
                if ($prefixMatching) {
                    $lexeme .= ':*';
                }

                return $lexeme;
            }, $lexemes));
        }, $selectKeywordTsqueryStmt)));

        if (0 === \count($keywordTsqueries)) {
            return '';
        }

        return '('.iter\join(') | (', $keywordTsqueries).')';
    }
}
