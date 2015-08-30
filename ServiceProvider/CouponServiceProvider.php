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

namespace Plugin\Coupon\ServiceProvider;

use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;

class CouponServiceProvider implements ServiceProviderInterface
{
    public function register(BaseApplication $app)
    {
        // ============================================================
        // Setting
        // [システム設定]-[プラグイン一覧]に設定リンクを表示する
        //$app->match('/' . $app["config"]["admin_route"] . '/plugin/order_pdf/config', '\\Plugin\\OrderPdf\\Controller\\OrderPdfController::index')->bind('plugin_OrderPdf_config');
        // ============================================================

        // ============================================================
        // リポジトリの登録
        // ============================================================
        // 不要？
        $app['eccube.plugin.coupon.repository.coupon_plugin'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\Coupon\Entity\CouponPlugin');
        });

        // クーポン情報テーブルリポジトリ
        $app['eccube.plugin.coupon.repository.coupon'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\Coupon\Entity\CouponCoupon');
        });

        // クーポン詳細情報テーブルリポジトリ
        $app['eccube.plugin.coupon.repository.coupon_detail'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\Coupon\Entity\CouponCouponDetail');
        });

        // 受注クーポン情報テーブルリポジトリ
        $app['eccube.plugin.coupon.repository.coupon_order'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\Coupon\Entity\CouponCouponOrder');
        });

        // ============================================================
        // コントローラの登録
        // ============================================================
        // クーポンの一覧
        $app->match('/' . $app["config"]["admin_route"] . '/coupon', '\\Plugin\\Coupon\\Controller\CouponController::index')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_coupon_list');

        // クーポンの新規先
        $app->match('/' . $app["config"]["admin_route"] . '/coupon/new', '\\Plugin\\Coupon\\Controller\CouponController::create')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_coupon_new');

        // クーポンの編集
        $app->match('/' . $app["config"]["admin_route"] . '/coupon/edit/{id}', '\\Plugin\\Coupon\\Controller\CouponController::edit')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_coupon_edit');

        // クーポンの新規作成・編集確定
        $app->match('/' . $app["config"]["admin_route"] . '/coupon/commit', '\\Plugin\\Coupon\\Controller\CouponController::commit')
        ->value('id', null)->assert('id', '\d+|')
        ->bind('admin_coupon_commit');

        // クーポンの有効/無効化
        $app->match('/' . $app["config"]["admin_route"] . '/coupon/enable/{id}', '\\Plugin\\Coupon\\Controller\CouponController::enable')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_coupon_enable');

        // クーポンの削除
        $app->match('/' . $app["config"]["admin_route"] . '/coupon/delete/{id}', '\\Plugin\\Coupon\\Controller\CouponController::delete')
        ->value('id', null)->assert('id', '\d+|')
        ->bind('admin_coupon_delete');

        // 商品検索画面表示
        $app->post('/' . $app["config"]["admin_route"] . '/coupon/search/product', '\\Plugin\\Coupon\\Controller\CouponSearchModelController::searchProduct')->bind('admin_coupon_search_product');
        // カテゴリ検索画面表示
        $app->post('/' . $app["config"]["admin_route"] . '/coupon/search/category', '\\Plugin\\Coupon\\Controller\CouponSearchModelController::searchCategory')->bind('admin_coupon_search_category');

        // ============================================================
        // Formの登録
        // ============================================================
        // 型登録
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new \Plugin\Coupon\Form\Type\CouponSearchType($app);
            $types[] = new \Plugin\Coupon\Form\Type\CouponType($app);
            $types[] = new \Plugin\Coupon\Form\Type\CouponDetailType($app);
            $types[] = new \Plugin\Coupon\Form\Type\CouponSearchCategoryType($app);
            return $types;
        }));

        // -----------------------------
        // サービスの登録
        // -----------------------------
        $app['eccube.plugin.coupon.service.coupon'] = $app->share(function () use ($app) {
            return new \Plugin\Coupon\Service\CouponService($app);
        });

        // ============================================================
        // メッセージ登録
        // ============================================================
        $app['translator'] = $app->share($app->extend('translator', function ($translator, \Silex\Application $app) {
            $translator->addLoader('yaml', new \Symfony\Component\Translation\Loader\YamlFileLoader());

            $file = __DIR__ . '/../Resource/locale/message.' . $app['locale'] . '.yml';
            if (file_exists($file)) {
                $translator->addResource('yaml', $file, $app['locale']);
            }

            return $translator;
        }));

        // ============================================================
        // メニュー登録
        // ============================================================
        $app['config'] = $app->share($app->extend('config', function ($config) {
            $addNavi['id'] = "admin_coupon";
            $addNavi['name'] = "クーポン";
            $addNavi['url'] = "admin_coupon_list";

            $nav = $config['nav'];
            foreach ($nav as $key => $val) {
                if ("order" == $val["id"]) {
                    $nav[$key]['child'][] = $addNavi;
                }
            }
            $config['nav'] = $nav;
            return $config;
        }));

    }

    public function boot(BaseApplication $app)
    {
    }
}
