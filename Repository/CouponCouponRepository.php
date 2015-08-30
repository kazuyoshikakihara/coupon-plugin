<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Plugin\Coupon\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Id\SequenceGenerator;
use Eccube\Common\Constant;

/**
 * OrderRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CouponCouponRepository extends EntityRepository
{
    /**
    * 検索条件での検索を行う。
    * s
    * @param unknown $searchData
    * @return \Doctrine\ORM\QueryBuilder
    */
    public function getQueryBuilderBySearchData($searchData)
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c')
            ->andWhere('c.del_flg = 0');

        if (!empty($searchData['coupon_cd']) && $searchData['coupon_cd']) {
            if (is_int($searchData['coupon_cd'])) {
                $qb
                ->andWhere('c.coupon_cd = :coupon_cd')
                ->setParameter('coupon_cd', $searchData['coupon_cd']);
            }
        }

        // Order By
        $qb->addOrderBy('c.id', 'DESC');

        return $qb;
    }

    /**
    * find all
    *
    * @return type
    */
    public function findAll()
    {

        $query = $this
        ->getEntityManager()
        ->createQuery('SELECT m FROM Plugin\Coupon\Entity\CouponCoupon m ORDER BY m.id DESC');
        $result = $query
        ->getResult(Query::HYDRATE_ARRAY);

        return $result;
    }

    /**
     * 有効なクーポンを1件取得する
     * @param unknown $couponCd
     * @param \DateTime $currenDateTime
     * @return unknown
     */
    public function findActiveCoupon($couponCd, \DateTime $currenDateTime) {

        // 時分秒を0に設定する
        $currenDateTime->setTime(0, 0, 0);

        $qb = $this->createQueryBuilder('c')->setMaxResults(1)->select('c')->Where('c.del_flg = 0');

        // クーポンコード
        $qb->andWhere('c.coupon_cd = :coupon_cd')
            ->setParameter('coupon_cd', $couponCd);

        // クーポンコード有効
        $qb->andWhere('c.enable_flag = :enable_flag')
            ->setParameter('enable_flag', Constant::ENABLED);

        // 有効期間(FROM)
        $qb->andWhere('c.available_from_date <= :cur_date_time OR c.available_from_date IS NULL')
            ->setParameter('cur_date_time', $currenDateTime);

        // 有効期間(TO)
        $qb->andWhere(':cur_date_time <= c.available_to_date OR c.available_to_date IS NULL')
            ->setParameter('cur_date_time', $currenDateTime);

        // 実行
        $result = null;
        $results = $qb->getQuery()->getResult();
        if(!is_null($results) && count($results) > 0) {
            $result = $results[0];
        }

        return $result;
    }

    /**
     * 有効なクーポンを全取得する
     * @param unknown $couponCd
     * @param \DateTime $currenDateTime
     * @return unknown
     */
    public function findActiveCouponAll(\DateTime $currenDateTime) {

        // 時分秒を0に設定する
        $currenDateTime->setTime(0, 0, 0);

        $qb = $this->createQueryBuilder('c')->select('c')->Where('c.del_flg = 0');

        // クーポンコード有効
        $qb->andWhere('c.enable_flag = :enable_flag')
            ->setParameter('enable_flag', Constant::ENABLED);

        // 有効期間(FROM)
        $qb->andWhere('c.available_from_date <= :cur_date_time OR c.available_from_date IS NULL')
            ->setParameter('cur_date_time', $currenDateTime);

        // 有効期間(TO)
        $qb->andWhere(':cur_date_time <= c.available_to_date OR c.available_to_date IS NULL')
            ->setParameter('cur_date_time', $currenDateTime);

        // 実行
        return $qb->getQuery()->getResult();
    }

}
