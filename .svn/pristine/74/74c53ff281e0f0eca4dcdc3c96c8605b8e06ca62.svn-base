<?php
/**
 * Created by PhpStorm.
 * User: wlw2533
 * Date: 2018/5/24
 * Time: 18:29
 */

namespace app\publish\service;

use app\common\cache\Cache;
use app\common\model\ebay\EbayActionLog;
use app\common\model\ebay\EbayCategory;
use app\common\model\ebay\EbayCategorySpecific;
use app\common\model\ebay\EbayCategorySpecificDetail;
use app\common\model\ebay\EbayListing;
use app\common\model\ebay\EbayListingVariation;
use app\common\model\ebay\EbayShipping;
use app\common\model\ebay\EbayTrans;
use app\common\service\ChannelAccountConst;
use app\common\service\Common;
use app\common\service\CommonQueuer;
use app\publish\helper\ebay\EbayPublish;
use think\Db;
use think\Exception;


class EbayDealApiInformation
{
    /**
     * @param $verb
     * @param $resText
     * @param array $data
     * @return array|bool
     * @throws Exception
     */
    public function dealWithApiResponse(string $verb, &$resText, $data=[])
    {
        try {
            if (!isset($resText[$verb.'Response'])) {
                return ['result'=>false,'message'=>'网络错误，请重试'];
            }
            $response = $resText[$verb.'Response'];
            if ($response['Ack'] == 'Failure' && $verb != 'EndItem') {
                $errorMsg = EbayPublish::dealEbayApiError($response);
                return ['result'=>false,'message'=>json_encode($errorMsg,JSON_UNESCAPED_UNICODE)];
            }
            switch ($verb) {
                case 'GeteBayDetails':
                    switch ($data['detail_name']) {
                        case 'ShippingServiceDetails':
                            if ($response['Ack'] == "Failure") {
                                return false;
                            }
                            $boolType = ['false'=>0, 'true'=>1];
                            $shippings = $response['ShippingServiceDetails'];
                            unset($response);
                            $rows = [];
                            $newShippingServiceIds = [];
                            foreach ($shippings as $k => $shipping) {//打包新信息
                                $rows[$k]['site'] = $data['site_id'];
                                isset($shipping['Description']) && $rows[$k]['description'] = $shipping['Description'];
                                isset($shipping['InternationalService']) && $rows[$k]['international_service'] = $boolType[$shipping['InternationalService']];
                                $rows[$k]['shipping_service'] = $shipping['ShippingService'];
                                $rows[$k]['shipping_service_id'] = intval($shipping['ShippingServiceID']);
                                isset($shipping['ShippingTimeMax']) && $rows[$k]['shipping_time_max'] = $shipping['ShippingTimeMax'];
                                isset($shipping['ShippingTimeMin']) && $rows[$k]['shipping_time_min'] = $shipping['ShippingTimeMin'];
                                isset($shipping['ValidForSellingFlow']) && $rows[$k]['valid_for_selling_flow'] = $boolType[$shipping['ValidForSellingFlow']];
                                isset($shipping['ShippingCarrier']) && $rows[$k]['shipping_carrier'] = $shipping['ShippingCarrier'];
//                                $rows[$k]['UpdateTime'] = $shipping['UpdateTime'];
                                isset($shipping['DimensionsRequired']) && $rows[$k]['dimensions_required'] = $boolType[$shipping['DimensionsRequired']];
                                isset($shipping['ShippingCategory']) && $rows[$k]['shipping_category'] = $shipping['ShippingCategory'];
                                isset($shipping['WeightRequired']) && $rows[$k]['weight_required'] = $boolType[$shipping['WeightRequired']];
                                $newShippingServiceIds[] = intval($shipping['ShippingServiceID']);
                            }
                            unset($shippings);
                            $oldShippingServiceIds = EbayTrans::where(['site'=>$data['site_id']])->column('shipping_service_id');
                            $insertShippingServiceIds = array_diff($newShippingServiceIds, $oldShippingServiceIds);//新增
                            $delShippingServiceIds = array_diff($oldShippingServiceIds, $newShippingServiceIds);//删除
                            $updateShippingServiceIds = array_diff($newShippingServiceIds, $insertShippingServiceIds);//更新
//                            unset($newShippingServiceIds);
                            unset($oldShippingServiceIds);
                            //删除
                            if (!empty($delShippingServiceIds)) {
                                EbayTrans::destroy(['site'=>$data['site_id'], 'shipping_service_id'=>['in', $delShippingServiceIds]]);
                                unset($delShippingServiceIds);
                            }
                            //新增
                            if (!empty($insertShippingServiceIds)) {
                                $insertRows = [];
                                $tmpRows = $rows;
                                foreach ($insertShippingServiceIds as $insertShippingServiceId) {
                                    foreach ($tmpRows as $k => $row) {
                                        if ($row['shipping_service_id'] == $insertShippingServiceId) {
                                            $insertRows[] = $row;
                                            unset($rows[$k]);//释放掉新增的，剩下的都是需要更新的
                                            unset($newShippingServiceIds[$k]);
                                            break;
                                        }
                                    }
                                }
                                (new EbayTrans())->isUpdate(false)->saveAll($insertRows);
                                unset($tmpRows);
                                unset($insertRows);
                                unset($insertShippingServiceIds);
                            }
                            //更新
                            $shippingInfo = (new EbayTrans())->field('id, shipping_service_id')
                                ->where(['site'=>$data['site_id'], 'shipping_service_id'=>['in', $updateShippingServiceIds]])
                                ->select();
                            $flipShippingIds = array_flip($newShippingServiceIds);
                            foreach ($shippingInfo as $shipping) {
                                $rows[$flipShippingIds[$shipping['shipping_service_id']]]['id'] = $shipping['id'];
                            }
                            $rows = array_values($rows);
                            (new EbayTrans())->saveAll($rows);
                            unset($rows);
                            unset($shippingInfo);
                            unset($flipShippingIds);
                            unset($newShippingServiceIds);
                            return true;
                    }
                    break;
                case 'GetCategories':
                    if ($response['Ack'] == 'Failure') {
                        return false;
                    }
                    $CategoryArray = $response['CategoryArray']['Category'];
                    unset($response);
                    $newCategoryIds = [];
                    $rows = [];
                    !isset($CategoryArray[0]) && $CategoryArray = [$CategoryArray];
                    foreach ($CategoryArray as $k => $v) {
                        $rows[$k]['platform'] = "ebay";
                        $rows[$k]['site'] = $data['site_id'];
                        $rows[$k]['category_id'] = intval($v['CategoryID']);
                        $rows[$k]['category_level'] = $v['CategoryLevel'];
                        $rows[$k]['category_name'] = $v['CategoryName'];
                        $rows[$k]['category_parent_id'] = $v['CategoryParentID'];
                        if (isset($v['BestOfferEnabled']) && $v['BestOfferEnabled'] == 'true') {
                            $rows[$k]['best_offer_enabled'] = 1;
                        } else {
                            $rows[$k]['best_offer_enabled'] = 0;
                        }
                        if (isset($v['AutoPayEnabled']) && $v['AutoPayEnabled'] == 'true') {
                            $rows[$k]['auto_pay_enabled'] = 1;
                        } else {
                            $rows[$k]['auto_pay_enabled'] = 0;
                        }
                        if (isset($v['LeafCategory']) && $v['LeafCategory'] == 'true') {
                            $rows[$k]['leaf_category'] = 1;
                        } else {
                            $rows[$k]['leaf_category'] = 0;
                        }
                        if (isset($v['LSD']) && $v['LSD'] == 'true') {
                            $rows[$k]['lsd'] = 1;
                        } else {
                            $rows[$k]['lsd'] = 0;
                        }
                        $rows[$k]['up_date'] = time();
                        $newCategoryIds[$k] = intval($v['CategoryID']);
                    }
                    unset($CategoryArray);
                    $isTopLevel = (isset($data['level_limit']) && $data['level_limit'] == 1);
                    if ($isTopLevel) {//如果是顶层分类
                        $oldCategoryIds = EbayCategory::where(['category_level' => 1, 'site' => $data['site_id']])->column('category_id');
                    } else {
                        $oldCategoryIds = $this->getCategoiesByTopCategory($data['parent_id'], $data['site_id']);
                    }
                    $insertCategoryIds = array_diff($newCategoryIds, $oldCategoryIds);//需要插入的
                    $delCategoryIds = array_diff($oldCategoryIds, $newCategoryIds);//需要删除的
                    $updateCategoryIds = array_diff($newCategoryIds, $insertCategoryIds);//需要更新的
//                    unset($newCategoryIds);
                    unset($oldCategoryIds);
                    //删除
                    if (!empty($delCategoryIds)) {
                        if ($isTopLevel) {
                            $tmpDelIds = [];
                            foreach ($delCategoryIds as $delCategoryId) {//因为是顶层，删除的时候要同时删除其子分类
                                $tmp = $this->getCategoiesByTopCategory($delCategoryId, $data['site_id']);
                                $tmpDelIds = array_merge($tmpDelIds, $tmp);
                            }
                            $tmp = 0;//释放$tmp
                            $delCategoryIds = $tmpDelIds;
                            unset($tmpDelIds);
                        }
                        EbayCategory::destroy(['category_id'=>['in', $delCategoryIds], 'site'=>$data['site_id']]);
                        unset($delCategoryIds);
                    }
                    //新增，重新打包信息
                    if (!empty($insertCategoryIds)) {
                        $insertRows = [];
                        $tmpRows = $rows;
                        foreach ($insertCategoryIds as $insertCategoryId) {
                            foreach ($tmpRows as $k => $row) {
                                if ($row['category_id'] == $insertCategoryId) {
                                    $insertRows[] = $row;
                                    unset($rows[$k]);//释放掉新增的，剩下的都是需要更新的
                                    unset($newCategoryIds[$k]);
                                    break;
                                }
                            }
                        }
                        (new EbayCategory())->isUpdate(false)->saveAll($insertRows);
                        unset($tmpRows);
                        unset($insertRows);
                        unset($insertCategoryIds);
                    }
                    //更新， 重新打包信息
                    $oldCategoryInfo = (new EbayCategory())->field('id,category_id')
                        ->where(['category_id'=>['in', $updateCategoryIds], 'site'=>$data['site_id']])->select();
                    $newCategoryIds = array_flip($newCategoryIds);
                    foreach ( $oldCategoryInfo as $oldCategory) {
                        $rows[$newCategoryIds[$oldCategory['category_id']]]['id'] = $oldCategory['id'];
                    }
                    unset($oldCategoryInfo);
                    unset($newCategoryIds);
                    $rows = array_values($rows);
                    (new EbayCategory())->saveAll($rows);
                    unset($rows);
                    //                    } else {
//                        $insertCategoryIds = array_diff($newCategoryIds, $oldCategoryIds);//需要插入的
//                        $delCategoryIds = array_diff($oldCategoryIds, $newCategoryIds);//需要删除的
//                        $updateCategoryIds = array_diff($newCategoryIds, $insertCategoryIds, $delCategoryIds);//需要更新的
//                        unset($newCategoryIds);
//                        unset($CategoryArray);
//                        //删除
//                        if (!empty($delCategoryIds)) {
//                            EbayCategory::destroy(['category_id' => ['in', $delCategoryIds], 'site' => $data['site_id']]);
//                        }
//                        //新增，
//
//                    }
//
//                    if (!empty($newCategoryIds)) {
//                        $map['category_id'] = ['not in', $newCategoryIds];
//                        $map['site'] = $data['site_id'];
//                        $map['category_level'] = 1;
//                        EbayCategory::destroy($map);//顶层分类不在更新列表里的删除掉
//                    }
                    return true;
                case 'GetCategoryFeatures':
                    if ($response['Ack'] == 'Failure') {
                        return false;
                    }
                    $field = $data['feature_id'] == 'CompatibilityEnabled' ? 'item_compatibility_enabled' : 'variations_enabled';//数据库对应字段
                    $feature = $data['feature_id'] == 'CompatibilityEnabled' ? 'ItemCompatibilityEnabled' : 'VariationsEnabled';//返回的特性名
                    $bool = ['Disabled' => 0, 'false' => 0, 'Enabled' => 1, 'true' => 1, 'ByApplication' => 1, 'BySpecification'=>1];
                    $allSubCategoryIds = $this->getCategoiesByTopCategory($data['category_id'], $data['site_id']);
                    $siteDefault = $bool[$response['SiteDefaults'][$feature]];
                    if (!isset($response['Category'])) {//如果全部分类使用的站点默认，可能会没有返回Category,所以要检测
                        EbayCategory::update([$field=>$siteDefault], ['category_id'=>['in', $allSubCategoryIds], 'site'=>$data['site_id']]);
                        return true;
                    }
                    $categories = $response['Category'];
                    $categoryIds = [];
                    unset($response);
                    if (!isset($categories[0])) {//没有索引0的情况下，仅返回了一个分类
                        $featureValue = $bool[$categories[$feature]];
                        $tmpCategoryId = intval($categories['CategoryID']);
                        if ($tmpCategoryId == $data['category_id']) {//是顶层分类
                            EbayCategory::update([$field=>$featureValue],
                                ['category_id'=>['in', $allSubCategoryIds], 'site'=>$data['site_id']]);
                        } else {//不是顶层分类
                            $subCategoryIds = $this->getCategoiesByTopCategory($tmpCategoryId, $data['site_id']);
                            $notUseDefaultCategoryIds = array_intersect($allSubCategoryIds, $subCategoryIds);//未使用站点默认
                            $useDefaultCategoryIds = array_diff($allSubCategoryIds, $subCategoryIds);
                            EbayCategory::update([$field=>$featureValue],
                                ['category_id'=>['in', $notUseDefaultCategoryIds], 'site'=>$data['site_id']]);
                            EbayCategory::update([$field=>$siteDefault],
                                ['category_id'=>['in', $useDefaultCategoryIds], 'site'=>$data['site_id']]);
                            unset($notUseDefaultCategoryIds);
                            unset($useDefaultCategoryIds);
                        }
                    } else {//返回了多个
                        $rows = [];
                        $levelCategoryIds = [];
                        foreach ($categories as $k => $category) {
                            $tmpCategoryId = intval($category['CategoryID']);
                            $categoryIds[$k] = $tmpCategoryId;
                            $level = EbayCategory::where(['category_id' => $tmpCategoryId, 'site' => $data['site_id']])
                                ->value('category_level');
                            $levelCategoryIds[$level][] = $tmpCategoryId;
                            $rows[$k] = $bool[$category[$feature]];
                        }
                        $flipCategoryIds = array_flip($categoryIds);
                        krsort($levelCategoryIds);//降序排列，更新时，先更新下层
                        $filterCategoryIds = [];
                        foreach ($levelCategoryIds as $levelCategoryId) {
                            foreach ($levelCategoryId as $categoryId) {
                                $subCategoryIds = $this->getCategoiesByTopCategory($categoryId, $data['site_id']);
                                $subCategoryIds = array_diff($subCategoryIds, $filterCategoryIds);//过滤掉已更新的
                                $featureValue = $rows[$flipCategoryIds[$categoryId]];
                                EbayCategory::update([$field => $featureValue],
                                    ['category_id' => ['in', $subCategoryIds], 'site' => $data['site_id']]);
                                $filterCategoryIds = array_merge($filterCategoryIds, $subCategoryIds);
                            }
                        }
                        unset($flipCategoryIds);
                        unset($levelCategoryIds);
                        unset($categoryIds);
                        //未更新的使用站点默认
                        $updateCategoryIds = array_diff($allSubCategoryIds, $filterCategoryIds);
                        if (!empty($updateCategoryIds)) {
                            EbayCategory::update([$field => $siteDefault],
                                ['category_id' => ['in', $updateCategoryIds], 'site' => $data['site_id']]);
                        }
                        unset($updateCategoryIds);
                        unset($filterCategoryIds);
                    }
                    unset($allSubCategoryIds);
                    return true;
                case 'GetCategorySpecifics':
                    if ($response['Ack'] != 'Success') {
                        return false;
                    }

                    $recommendations = $response['Recommendations'];
                    !isset($recommendations[0]) && $recommendations = [$recommendations];
                    foreach ($recommendations as $key => $recommendation) {
                        if (!isset($recommendation['NameRecommendation'])) continue;
                        $nameRecommendations = $recommendation['NameRecommendation'];
                        !isset($nameRecommendations[0]) &&  $nameRecommendations = [$nameRecommendations];
                        foreach ($nameRecommendations as $nameRecommendation) {
                            $rowsSpecific = [];
                            $rowsDetail = [];
                            $map = [];
                            $rowsSpecific['category_id'] = intval($recommendation['CategoryID']);
                            $rowsSpecific['category_specific_name'] = $nameRecommendation['Name'];
                            $rowsSpecific['platform'] = 'ebay';
                            $rowsSpecific['site'] = $data['site_id'];
                            $validationRules = $nameRecommendation['ValidationRules'];
                            isset($validationRules['ValueType']) && $rowsSpecific['value_type'] = $validationRules['ValueType'];
                            isset($validationRules['MaxValues']) && $rowsSpecific['max_values'] = $validationRules['MaxValues'];
                            isset($validationRules['MinValues']) && $rowsSpecific['min_values'] = $validationRules['MinValues'];
                            isset($validationRules['SelectionMode']) && $rowsSpecific['selection_mode'] = $validationRules['SelectionMode'];
                            isset($validationRules['VariationSpecifics']) && $rowsSpecific['variation_specifics'] = $validationRules['VariationSpecifics'];
                            isset($validationRules['VariationPicture']) && $rowsSpecific['variation_picture'] = $validationRules['VariationPicture'];
                            isset($validationRules['Relationship']) && $rowsSpecific['relationship'] = 1;
                            $map['site'] = $data['site_id'];
                            $map['category_specific_name'] = $rowsSpecific['category_specific_name'];
                            $map['category_id'] = $rowsSpecific['category_id'];
                            $specificInfo = EbayCategorySpecific::get($map);
                            if ($specificInfo) {
                                $specificId = $specificInfo->id;
                                EbayCategorySpecific::update($rowsSpecific, ['id'=>$specificId]);
                            } else {
                                $specificId = (new EbayCategorySpecific())->insertGetId($rowsSpecific);
                            }
                            //detail
                            if (!isset($nameRecommendation['ValueRecommendation'])) continue;
                            $valueRecommendations = $nameRecommendation['ValueRecommendation'];
                            !isset($valueRecommendations[0]) && $valueRecommendations = [$valueRecommendations];
                            $i = 0;
                            foreach ($valueRecommendations as $k => $valueRecommendation) {
                                if (isset($valueRecommendation['ValidationRules']['Relationship'])) {
                                    $relationships = $valueRecommendation['ValidationRules']['Relationship'];
                                    !isset($relationships) &&  $relationships = [$relationships];
                                    foreach ($relationships as $relationship) {
                                        $rowsDetail[$i]['ebay_specific_id'] = $specificId;
                                        $rowsDetail[$i]['category_specific_name'] = $nameRecommendation['Name'];
                                        $rowsDetail[$i]['category_specific_value'] = isset($valueRecommendation['Value']) ? $valueRecommendation['Value'] : '';
                                        $rowsDetail[$i]['category_id'] = $rowsSpecific['category_id'];
                                        $rowsDetail[$i]['site'] = $data['site_id'];
                                        $rowsDetail[$i]['parent_name'] = isset($relationship['ParentName']) ? $relationship['ParentName'] : '';
                                        $rowsDetail[$i]['parent_value'] = isset($relationship['ParentValue']) ? $relationship['ParentValue'] : '';
                                        $i++;
                                    }
                                } else {
                                    $rowsDetail[$i]['ebay_specific_id'] = $specificId;
                                    $rowsDetail[$i]['category_specific_name'] = $nameRecommendation['Name'];
                                    $rowsDetail[$i]['category_specific_value'] = isset($valueRecommendation['Value']) ? $valueRecommendation['Value'] : '';
                                    $rowsDetail[$i]['category_id'] = $rowsSpecific['category_id'];
                                    $rowsDetail[$i]['site'] = $data['site_id'];
                                    $i++;
                                }
                            }
                            $map['ebay_specific_id'] = $specificId;
                            EbayCategorySpecificDetail::destroy($map);
                            (new EbayCategorySpecificDetail())->isUpdate(false)->saveAll($rowsDetail);
                        }
                    }
                    return true;
                    break;
                case 'UploadSiteHostedPictures':
                    if ($response['Ack'] == 'Success' || $response['Ack'] == 'Warning' ) {
                        $siteHostedPictureDetails = $response['SiteHostedPictureDetails'];
                        $url = isset($siteHostedPictureDetails['PictureSetMember'][3]['MemberURL']) ?
                            $siteHostedPictureDetails['PictureSetMember'][3]['MemberURL'] : $siteHostedPictureDetails['FullURL'];
                        return ['result'=>true, 'data'=>$url];
                    } else {
                        $errors = $response['Errors'];
                        $errorMsg = [];
                        if (isset($errors[0])) {
                            foreach ($errors as $error) {
                                $errorMsg[] = $error['Errors']['ErrorCode'].':'.$error['Errors']['LongMessage'];
                            }
                        } else {
                            $errorMsg = $errors['ErrorCode'].':'.$errors['LongMessage'];
                        }
                        return ['result'=>false, 'message'=>$errorMsg];
                    }
                    break;
                case 'VerifyAddFixedPriceItem':
                case 'VerifyAddItem':
                    if ($response['Ack'] == 'Failure') {//失败
                        $errorInfo = $response['Errors'];
                        $errors = isset($errorInfo[0]) ? $errorInfo : [$errorInfo];
                        $errorMsg = [];
                        foreach ($errors as $error) {
                            if ($error['SeverityCode'] == 'Error') {
                                $errorMsg[] = $error['ErrorCode'] . ':' . $error['LongMessage'];
                            }
                        }
                        return ['result'=>false,'message'=>json_encode($errorMsg)];
                    } else if ($response['Ack'] == 'Success' || $response['Ack'] == 'Warning') {//刊登成功
                        $feesInfo = $this->getListingFeesFromReponse($response['Fees']);
                        return ['result'=>true,'data'=>$feesInfo];
                    }
                    break;
                case 'AddItem':
                case 'AddFixedPriceItem':
                    if ($response['Ack'] == 'Failure') {
                        $errorInfo = $response['Errors'];
                        $errors = isset($errorInfo[0]) ? $errorInfo : [$errorInfo];
                        $errorMsg = [];
                        foreach ($errors as $error) {
                            if ($error['SeverityCode'] == 'Error') {
                                $errorMsg[] = $error['ErrorCode'] . ':'.$error['SeverityCode'].':' . $error['LongMessage'];
                            }
                        }
                        if (isset($response['Message'])) {
                            $errorMsg[] = $response['Message'];
                        }
                        return ['result'=>false,'message'=>json_encode($errorMsg)];
                    }
                    if ($response['Ack'] == 'Success' || $response['Ack'] == 'Warning') {//刊登成功
//                        $updateInfo['listing_status'] = 3;
//                        $updateInfo['item_id'] = $response['ItemID'];
//                        $updateInfo['start_date'] = time();
//                        EbayListing::update($updateInfo,['id'=>$data['list']['id']]);//先把状态和item id记录下来，避免后面数据处理出现异常写入失败
                        try {
                            //刊登成功后push到"SPU上架实时统计队列"
                            if ($data['list']['variation']) {//多属性
                                $skuCount = EbayListingVariation::where('listing_id', $data['list']['id'])->count();
                            }
                            $skuCount = empty($skuCount) ? 1 : $skuCount;
                            $param = [
                                'channel_id' => ChannelAccountConst::channel_ebay,
                                'account_id' => $data['list']['account_id'],
                                'shelf_id' => $data['list']['user_id'],
                                'goods_id' => $data['list']['goods_id'],
                                'times' => 1, //实时=1
                                'quantity' => $skuCount,//变体数量
                                'dateline' => time()
                            ];
                            (new CommonQueuer(\app\report\queue\StatisticByPublishSpuQueue::class))->push($param);
                        } catch (\Exception $e) {
                            //不处理
                        }
                        $feesInfo = $this->getListingFeesFromReponse($response['Fees'], true);
                        $list['item_id'] = $response['ItemID'];
                        $list['listing_fee'] = $feesInfo['ListingFee']??0;
                        $list['insertion_fee'] = $feesInfo['InsertionFee']??0;
                        $list['end_date'] = strtotime($response['EndTime'])??0;
                        $list['start_date'] = time();
                        return ['result'=>true,'data'=>$list];
//                        $updateInfo['insertion_fee'] = isset($feesInfo['InsertionFee']) ? $feesInfo['InsertionFee'] : 0;
//                        $updateInfo['listing_fee'] = isset($feesInfo['ListingFee']) ? $feesInfo['ListingFee'] : 0;
//                        unset($updateSetInfo['message']);
//                        unset($feesInfo);
                    }
                    return ['result'=>false,'message'=>'未知错误'];
                    break;
                case 'ReviseInventoryStatus':
                    if ($response['Ack'] == 'Failure') {
                        $errorInfo = $response['Errors'];
                        $errors = isset($errorInfo[0]) ? $errorInfo : [$errorInfo];
                        $errorMsg = [];
                        foreach ($errors as $error) {
                            if ($error['SeverityCode'] == 'Error') {
                                $errorMsg[] = $error['ErrorCode'] . ':'.$error['SeverityCode'].':' . $error['LongMessage'];
                            }
                        }
                        $message = empty($errorMsg) ? json_encode($errors) : implode("\n", $errorMsg);
                        EbayActionLog::update(['status'=>3, 'message'=>$message], ['id'=>$data['logId']]);
                        EbayListing::update(['listing_status'=>EbayPublish::PUBLISH_STATUS['updateFail']], ['id'=>$data['listing']['id']]);
                    } else if ($response['Ack'] == 'Success' || $response['Ack'] == 'Warning') {//更新成功
//                        EbayActionLog::update(['status'=>2], ['id'=>$data['logId']]);
                        EbayListing::update(['listing_status'=>EbayPublish::PUBLISH_STATUS['publishSuccess']], ['id'=>$data['listing']['id']]);
                        $inventoryStatus = $response['InventoryStatus'];
                        if ($data['listing']['variation'] == 0) {//单属性
                            $update['quantity'] = $inventoryStatus['Quantity']??0;
                            $update['start_price'] = $inventoryStatus['StartPrice'];
                            EbayListing::update($update, ['id'=>$data['listing']['id']]);
                        } else {//多属性
                            !isset($inventoryStatus[0]) && $inventoryStatus = [$inventoryStatus];
                            foreach ($inventoryStatus as $inventory) {
                                $varUpdate['v_qty'] = $inventory['Quantity']??0;
                                $varUpdate['v_price'] = $inventory['StartPrice'];
                                EbayListingVariation::update($varUpdate, ['listing_id'=>$data['listing']['id'],
                                    'channel_map_code'=>$inventory['SKU']]);
                            }
                        }
                        return ['result'=>true];
                    }
                    break;
                case 'ReviseFixedPriceItem':
                case 'ReviseItem':
                    return ['result'=>true];
                    break;
                case 'GetSuggestedCategories':
                    if ($response['Ack'] == 'Failure') {
                        return false;
                    }
                    if (!isset($response['SuggestedCategoryArray']['SuggestedCategory'])) {
                        return false;
                    }
                    $suggestedCategories = $response['SuggestedCategoryArray']['SuggestedCategory'];
                    !isset($suggestedCategories[0]) && $suggestedCategories = [$suggestedCategories];
                    $categoryIds = [];
                    foreach ($suggestedCategories as $suggestedCategory) {
                        $category = $suggestedCategory['Category'];
                        $categoryIds[] = $category['CategoryID'];
                    }
                    return $categoryIds;
                    break;
                case 'EndItem':
                    if ($response['Ack'] == 'Failure') {
                        $errorMsg = EbayPublish::dealEbayApiError($response);
                        if (isset($errorMsg[1047])) {//此错误说明listing已经下架了
                            return ['result'=>true];
                        }
                        return ['result'=>false,'message'=>json_encode($errorMsg)];
                    }
                    return ['result'=>true];
                    break;

                default:
                    throw new Exception('无法找到对应的API回复');
                    break;
            }
        } catch (\Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }

    /**
     * @param $feesInfo
     * @return array|string
     * @throws Exception
     */
    private function getListingFeesFromReponse(array $feesInfo, bool $simple=false)
    {
        try {
            $detailFee = [];
            //feesinfo格式为Fees = [ 'Fee'=>
            //                        0=>[
            //                               'Name'=>'SubtitleFee',
            //                              'Fee@Atts'=>['currencyID'=>USD]
            //                              'Fee'=>0.0
            //                           ]
            //                       1=>[
            //                              'Name'=>'InsertionFee',
            //                              'Fee@Atts'=>['currencyID'=>USD],
            //                              'Fee'=>0.05
            //                          ]
            //                      3=>[
            //                              'Name'=>'ListingFee',//总费用
            //                              'Fee@Atts'=>['currencyID'=>USD],
            //                              'Fee'=>0.05,
            //                              'PromotionalDiscount@Atts'=>['currencyID'=>USD],
            //                              'PromotionalDiscount'=>0.05
            //                          ]
            //                       ...

            foreach ($feesInfo as $fees) {
                foreach ($fees as $fee) {
                    if ($fee['Fee'] != '0.0') {
                        $detailFee[$fee['Name']] = $simple ? $fee['Fee'] : $fee['Fee'].' '.$fee['Fee@Atts']['currencyID'];
                        if (isset($fee['PromotionalDiscount']) && !$simple) {
                            $detailFee[$fee['Name']] .= ','.$fee['PromotionalDiscount'].' '.$fee['PromotionalDiscount@Atts']['currencyID'];
                        }
                    }
                }
            }
            if (isset($detailFee['ListingFee'])) {
                $tmp = $detailFee['ListingFee'];
                unset($detailFee['ListingFee']);
                $detailFee['ListingFee'] = $tmp;//把总费用ListingFee排在最后
            }
            return $detailFee;
        } catch(Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 根据顶层分类Id,获取其下所有的分类id
     * @param int $categoryId
     * @param int $siteId
     * @return array
     * @throws Exception
     */
    private function getCategoiesByTopCategory(int $categoryId, int $siteId, bool $setLevel=false)
    {
        try {
            $categoryLevelIds = [$categoryId];
            $level = 1;
            if ($setLevel) {
                $categoryIds[$level++] = [$categoryId];
            } else {
                $categoryIds = [$categoryId];
            }
            while (1) {
                $subCategoryIds = EbayCategory::where(['category_parent_id'=>['in', $categoryLevelIds],
                                                          'site'=>$siteId,
                                                          'category_id'=>['not in', $categoryLevelIds]])->column('category_id');
                if (empty($subCategoryIds)) break;
                if ($setLevel) {
                    $categoryIds[$level++] = $subCategoryIds;
                } else {
                    $categoryIds = array_merge($categoryIds, $subCategoryIds);
                }
                $categoryLevelIds = $subCategoryIds;
            }
            return $categoryIds;
        } catch(Exception $e) {
            throw new Exception($e->getFile().'|'.$e->getLine().'|'.$e->getMessage());
        }
    }


    /**
     * @return array
     * @throws Exception
     */
    private function getUser()
    {
        try {
            $userInfo = Common::getUserInfo();
            return $userInfo ? $userInfo : [];
        } catch (Exception $e) {
            throw new Exception($e->getFile()|$e->getLine()|$e-getMessage());
        }
    }

}
