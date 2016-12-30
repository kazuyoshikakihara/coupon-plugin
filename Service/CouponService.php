<?php
/*
 * This file is part of the Coupon plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon\Service;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Entity\OrderDetail;
use Plugin\Coupon\Entity\Coupon;
use Plugin\Coupon\Entity\CouponDetail;
use Plugin\Coupon\Entity\CouponOrder;
use Eccube\Entity\Category;

/**
 * Class CouponService.
 */
class CouponService
{
    /** @var \Eccube\Application */
    public $app;

    /**
     * コンストラクタ
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * クーポン情報を新規登録する.
     *
     * @param array $data
     *
     * @return bool
     */
    public function createCoupon($data)
    {
        // クーポン詳細情報を生成する
        $coupon = $this->newCoupon($data);
        $em = $this->app['orm.em'];
        // クーポン情報を登録する
        $em->persist($coupon);
        $em->flush($coupon);
        // クーポン詳細情報を登録する
        foreach ($data['CouponDetails'] as $detail) {
            $couponDetail = $this->newCouponDetail($coupon, $detail);
            $em->persist($couponDetail);
            $em->flush($couponDetail);
        }

        return true;
    }

    /**
     * クーポン情報を更新する.
     *
     * @param array $data
     *
     * @return bool
     */
    public function updateCoupon($data)
    {
        $em = $this->app['orm.em'];
        // クーポン情報を取得する
        $coupon = $this->app['eccube.plugin.coupon.repository.coupon']->find($data['id']);
        if (is_null($coupon)) {
            false;
        }

        // クーポン情報を書き換える
        $coupon->setCouponCd($data['coupon_cd']);
        $coupon->setCouponName($data['coupon_name']);
        $coupon->setCouponType($data['coupon_type']);
        $coupon->setDiscountType($data['discount_type']);
        $coupon->setDiscountPrice($data['discount_price']);
        $coupon->setDiscountRate($data['discount_rate']);
        $coupon->setAvailableFromDate($data['available_from_date']);
        $coupon->setAvailableToDate($data['available_to_date']);
        $coupon->setCouponUseTime($data['coupon_use_time']);

        // クーポン情報を更新する
        $em->persist($coupon);
        // クーポン詳細情報を取得する
        $details = $coupon->getCouponDetails();
        // クーポン詳細情報を一旦削除する
        foreach ($details as $detail) {
            // クーポン詳細情報を書き換える
            $detail->setDelFlg(Constant::ENABLED);
            $em->persist($detail);
        }
        // クーポン詳細情報を登録/更新する
        $details = $data->getCouponDetails();
        foreach ($details as $detail) {
            $couponDetail = null;
            if (is_null($detail->getId())) {
                $couponDetail = new CouponDetail();
                $couponDetail = $detail;
                $couponDetail->setCoupon($coupon);
                $couponDetail->setCouponType($coupon->getCouponType());
            } else {
                $couponDetail = $this->app['eccube.plugin.coupon.repository.coupon_detail']->find($detail->getId());
            }
            $couponDetail->setDelFlg(Constant::DISABLED);
            $em->persist($couponDetail);
            $em->flush($couponDetail);
        }

        return true;
    }

    /**
     * クーポン情報を有効/無効にする.
     *
     * @param int $couponId
     *
     * @return bool
     */
    public function enableCoupon($couponId)
    {
        $em = $this->app['orm.em'];
        // クーポン情報を取得する
        $coupon = $this->app['eccube.plugin.coupon.repository.coupon']->find($couponId);
        if (is_null($coupon)) {
            return false;
        }
        // クーポン情報を書き換える
        $coupon->setEnableFlag($coupon->getEnableFlag() == 0 ? 1 : 0);
        // クーポン情報を登録する
        $em->persist($coupon);
        $em->flush($coupon);

        return true;
    }

    /**
     * クーポン情報を削除する.
     *
     * @param int $couponId
     *
     * @return bool
     */
    public function deleteCoupon($couponId)
    {
        $em = $this->app['orm.em'];
        // クーポン情報を取得する
        $coupon = $this->app['eccube.plugin.coupon.repository.coupon']->find($couponId);
        if (is_null($coupon)) {
            return false;
        }
        // クーポン情報を書き換える
        $coupon->setDelFlg(Constant::ENABLED);
        // クーポン情報を登録する
        $em->persist($coupon);
        $em->flush($coupon);
        // クーポン詳細情報を取得する
        $details = $coupon->getCouponDetails();
        foreach ($details as $detail) {
            // クーポン詳細情報を書き換える
            $detail->setDelFlg(Constant::ENABLED);
            $em->persist($detail);
            $em->flush($detail);
        }

        return true;
    }

    /**
     * クーポンコードを生成する.
     *
     * @param int $length
     *
     * @return string
     */
    public function generateCouponCd($length = 12)
    {
        $couponCd = substr(base_convert(md5(uniqid()), 16, 36), 0, $length);

        return $couponCd;
    }

    /**
     * クーポン情報を生成する.
     *
     * @param $data
     *
     * @return Coupon
     */
    protected function newCoupon($data)
    {
        $coupon = new Coupon();
        $coupon->setCouponCd($data['coupon_cd']);
        $coupon->setCouponName($data['coupon_name']);
        $coupon->setCouponType($data['coupon_type']);
        $coupon->setDiscountType($data['discount_type']);
        $coupon->setDiscountPrice($data['discount_price']);
        $coupon->setDiscountRate($data['discount_rate']);
        $coupon->setCouponUseTime($data['coupon_use_time']);
        $coupon->setEnableFlag(Constant::ENABLED);
        $coupon->setDelFlg(Constant::DISABLED);
        $coupon->setAvailableFromDate($data['available_from_date']);
        $coupon->setAvailableToDate($data['available_to_date']);

        return $coupon;
    }

    /**
     * クーポン詳細情報を生成する.
     *
     * @param Coupon       $coupon
     * @param CouponDetail $detail
     *
     * @return CouponDetail
     */
    protected function newCouponDetail(Coupon $coupon, CouponDetail $detail)
    {
        $couponDetail = new CouponDetail();
        $couponDetail->setCoupon($coupon);
        $couponDetail->setCouponType($coupon->getCouponType());
        $couponDetail->setCategory($detail->getCategory());
        $couponDetail->setProduct($detail->getProduct());
        $couponDetail->setDelFlg(Constant::DISABLED);

        return $couponDetail;
    }

    /**
     * 注文にクーポン対象商品が含まれているか確認する.
     *
     * @param Coupon $Coupon
     * @param Order  $Order
     *
     * @return bool
     */
    public function existsCouponProduct(Coupon $Coupon, Order $Order)
    {
        $couponProducts = false;
        if (!is_null($Coupon)) {
            // 対象商品の存在確認
            if ($Coupon->getCouponType() == 1) {
                // 商品の場合
                $couponProducts = $this->containsProduct($Coupon, $Order);
            } elseif ($Coupon->getCouponType() == 2) {
                // カテゴリの場合
                $couponProducts = $this->containsCategory($Coupon, $Order);
            } elseif ($Coupon->getCouponType() == 3) {
                // all product
                // 一致する商品IDがあればtrueを返す
                foreach ($Order->getOrderDetails() as $detail) {
                    /* @var $detail OrderDetail */
                    $couponProducts[$detail->getProductClass()->getId()]['price'] = $detail->getPriceIncTax();
                    $couponProducts[$detail->getProductClass()->getId()]['quantity'] = $detail->getQuantity();
                }
            }
        }

        return $couponProducts;
    }

    /**
     * 商品がクーポン適用の対象か調査する.
     *
     * @param Coupon $Coupon
     * @param Order  $Order
     *
     * @return array
     */
    private function containsProduct(Coupon $Coupon, Order $Order)
    {
        // クーポンの対象商品IDを配列にする
        $targetProductIds = array();
        $couponProducts = array();
        foreach ($Coupon->getCouponDetails() as $detail) {
            $targetProductIds[] = $detail->getProduct()->getId();
        }

        // 一致する商品IDがあればtrueを返す
        foreach ($Order->getOrderDetails() as $detail) {
            /* @var $detail OrderDetail */
            if (in_array($detail->getProduct()->getId(), $targetProductIds)) {
                $couponProducts[$detail->getProductClass()->getId()]['price'] = $detail->getPriceIncTax();
                $couponProducts[$detail->getProductClass()->getId()]['quantity'] = $detail->getQuantity();
            }
        }

        return $couponProducts;
    }

    /**
     * カテゴリがクーポン適用の対象か調査する.
     * 下位のカテゴリから上位のカテゴリに向けて検索する.
     *
     * @param Coupon $Coupon
     * @param Order  $Order
     *
     * @return array
     */
    private function containsCategory(Coupon $Coupon, Order $Order)
    {
        // クーポンの対象カテゴリIDを配列にする
        $targetCategoryIds = array();
        $couponProducts = array();
        foreach ($Coupon->getCouponDetails() as $detail) {
            $targetCategoryIds[] = $detail->getCategory()->getId();
        }

        // 受注データからカテゴリIDを取得する
        foreach ($Order->getOrderDetails() as $orderDetail) {
            /* @var $orderDetail OrderDetail */
            foreach ($orderDetail->getProduct()->getProductCategories() as $productCategory) {
                if ($this->existsDepthCategory($targetCategoryIds, $productCategory->getCategory())) {
                    $couponProducts[$orderDetail->getProductClass()->getId()]['price'] = $orderDetail->getPriceIncTax();
                    $couponProducts[$orderDetail->getProductClass()->getId()]['quantity'] = $orderDetail->getQuantity();
                }
            }
        }

        return $couponProducts;
    }

    /**
     * クーポン対象のカテゴリが存在するか確認にする.
     *
     * @param $targetCategoryIds
     * @param Category $Category
     *
     * @return bool
     */
    private function existsDepthCategory(&$targetCategoryIds, Category $Category)
    {
        // Categoryがnullならfalse
        if (is_null($Category)) {
            return false;
        }

        // 対象カテゴリか確認
        if (in_array($Category->getId(), $targetCategoryIds)) {
            return true;
        }

        // Categoryをテーブルから取得
        if (is_null($Category->getParent())) {
            return false;
        }

        // 親カテゴリをテーブルから取得
        $ParentCategory = $this->app['eccube.repository.category']->find($Category->getParent());
        if ($ParentCategory) {
            return false;
        }

        return $this->existsDepthCategory($targetCategoryIds, $ParentCategory);
    }

    /**
     * クーポン受注情報を保存する.
     *
     * @param Order    $Order
     * @param Coupon   $Coupon
     * @param int      $couponCd
     * @param Customer $Customer
     * @param int      $discount
     */
    public function saveCouponOrder(Order $Order, Coupon $Coupon, $couponCd, Customer $Customer, $discount)
    {
        if (is_null($Order)) {
            return;
        }

        $repository = $this->app['eccube.plugin.coupon.repository.coupon_order'];
        // クーポン受注情報を取得する
        $CouponOrder = $repository->findOneBy(array(
            'pre_order_id' => $Order->getPreOrderId(),
        ));

        if (is_null($CouponOrder)) {
            // 未登録の場合
            $CouponOrder = new CouponOrder();
            $CouponOrder->setOrderId($Order->getId());
            $CouponOrder->setPreOrderId($Order->getPreOrderId());
            $CouponOrder->setDelFlg(Constant::DISABLED);
        }

        // 更新対象データ
        if (is_null($Coupon) || (is_null($couponCd) || strlen($couponCd) == 0)) {
            // クーポンがない または クーポンコードが空の場合
            $CouponOrder->setCouponCd($couponCd);
            $CouponOrder->setCouponId(null);
        } else {
            // クーポン情報があるが、対象商品がない場合はクーポンIDにnullを設定する。
            // そうでない場合はクーポンIDを設定する
            if ($this->existsCouponProduct($Coupon, $Order)) {
                $CouponOrder->setCouponId($Coupon->getId());
            } else {
                $CouponOrder->setCouponId(null);
            }
            $CouponOrder->setCouponCd($Coupon->getCouponCd());
        }

        // ログイン済みの場合は, user_id取得
        if ($this->app->isGranted('ROLE_USER')) {
            $CouponOrder->setUserId($Customer->getId());
        } else {
            $CouponOrder->setEmail($Customer->getEmail());
        }

        // 割引金額をセット
        $CouponOrder->setDiscount($discount);
        $repository->save($CouponOrder);
    }

    /**
     * 合計、値引きを再計算する.
     *
     * @param Order  $Order
     * @param Coupon $Coupon
     * @param array  $couponProducts
     *
     * @return float|int|string
     *
     * @throws \Doctrine\ORM\NoResultException
     */
    public function recalcOrder(Order $Order, Coupon $Coupon, $couponProducts)
    {
        $discount = 0;
        // クーポンコードが存在する場合カートに入っている商品の値引き額を設定する
        if ($Coupon) {
            // 対象商品の存在確認.
            // 割引対象商品が存在する場合は値引き額を取得する
            if ($this->existsCouponProduct($Coupon, $Order)) {
                // 割引対象商品がある場合は値引き額を計算する
                if ($Coupon->getDiscountType() == 1) {
                    $discount = $Coupon->getDiscountPrice();
                } else {
                    // 課税区分の取得
                    $taxService = $this->app['eccube.service.tax_rule'];
                    $TaxRule = $this->app['eccube.repository.tax_rule']->getByRule();
                    // 値引き前の金額で割引率を算出する
                    $total = 0;
                    foreach ($couponProducts as $key => $value) {
                        $total += $value['price'] * $value['quantity'];
                    }
                    // 小数点以下は四捨五入
                    $discount = $taxService->calcTax(
                        $total,
                        $Coupon->getDiscountRate(),
                        $TaxRule->getCalcRule()->getId(),
                        $TaxRule->getTaxAdjust()
                    );
                }
            }
        }

        return $discount;
    }

    /**
     * カート内の商品(OrderDetail)がクーポン対象商品か確認する.
     *
     * @param Order $Order
     *
     * @return bool
     */
    public function isOrderInActiveCoupon(Order $Order)
    {
        // 有効なクーポン一覧を取得する
        $Coupons = $this->app['eccube.plugin.coupon.repository.coupon']->findActiveCouponAll();
        // 有効なクーポンを持つ商品の存在を確認する
        foreach ($Coupons as $Coupon) {
            if ($this->existsCouponProduct($Coupon, $Order)) {
                return true;
            }
        }

        return false;
    }

    /**
     * check coupon lower limit.
     *
     * @param array $productCoupon
     * @param int   $lowerLimitMoney
     *
     * @return bool
     */
    public function isLowerLimitCoupon($productCoupon, $lowerLimitMoney)
    {
        foreach ($productCoupon as $key => $value) {
            if ($value['price'] < $lowerLimitMoney) {
                return false;
            }
        }

        return true;
    }

    /**
     * クーポン受注情報を取得する.
     *
     * @param $preOrderId
     *
     * @return null|object
     */
    public function getCouponOrder($preOrderId)
    {
        $CouponOrder = $this->app['eccube.plugin.coupon.repository.coupon_order']
            ->findOneBy(array(
                'pre_order_id' => $preOrderId,
            ));

        return $CouponOrder;
    }

    /**
     *  ユーザはクーポン1回のみ利用できる.
     *
     * @param $couponCd
     * @param Customer $Customer
     *
     * @return bool
     */
    public function checkCouponUsedOrNot($couponCd, Customer $Customer)
    {
        $repository = $this->app['eccube.plugin.coupon.repository.coupon_order'];

        if ($this->app->isGranted('ROLE_USER')) {
            $result = $repository->findUseCoupon($couponCd, $Customer->getId());
        } else {
            $result = $repository->findUseCoupon($couponCd, $Customer->getEmail());
        }

        if (!$result) {
            return false;
        }

        return true;
    }

    /**
     *  ユーザはクーポン1回のみ利用できる.
     *
     * @param $couponCd
     * @param $orderId
     * @param Customer $Customer
     *
     * @return bool
     */
    public function checkCouponUsedOrNotBefore($couponCd, $orderId, Customer $Customer)
    {
        $repository = $this->app['eccube.plugin.coupon.repository.coupon_order'];
        if ($this->app->isGranted('ROLE_USER')) {
            $CouponOrders = $repository->findUseCouponBefore($couponCd, $orderId, $Customer->getId());
        } else {
            $CouponOrders = $repository->findUseCouponBefore($couponCd, $orderId, $Customer->getEmail());
        }

        if ($CouponOrders) {
            // 存在すれば既に受注として利用されていないかチェック
            foreach ($CouponOrders as $CouponOrder) {
                $Order = $this->app['eccube.repository.order']->find($CouponOrder->getOrderId());
                if ($Order) {
                    if ($Order->getOrderStatus()->getId() != $this->app['config']['order_processing']) {
                        // 同一のクーポンコードで既に受注データが存在している
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
