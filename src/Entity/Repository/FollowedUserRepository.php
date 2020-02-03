<?php

namespace App\Entity\Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityRepository;

class FollowedUserRepository extends EntityRepository
{
    /**
     * @param string $email
     * @param int $limit
     * @param int $offset
     * @return array
     * @throws DBALException
     */
    public function findFollowedUsers(string $email, int $limit = 10, int $offset = 0): array
    {
        $sqlQuery = <<<SQL
select
    fu.who_user_id,
    who.email               who_email,
    fu.whom_user_id,
    whom.email              whom_email,
    sum(fu.earned_amount)   earned_amount 
from actiondata.followed_user fu
left join userdata."user" who on who.id = fu.who_user_id
left join userdata."user" whom on whom.id = fu.whom_user_id
where
    who.email = :email
group by fu.who_user_id, who.email, fu.whom_user_id, whom.email
order by whom.email
limit :limit offset :offset
SQL;

        return $this->_em->getConnection()->executeQuery($sqlQuery, [
            'email'  => $email,
            'limit'  => $limit,
            'offset' => $offset
        ])->fetchAll(FetchMode::ASSOCIATIVE);
    }
}