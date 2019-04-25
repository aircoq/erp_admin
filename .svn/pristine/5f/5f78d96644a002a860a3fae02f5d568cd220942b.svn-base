<?php
/**
 * Created by PhpStorm.
 * User: adminstrator
 * Date: 2019/3/1
 * Time: 9:36
 */

namespace app\customerservice\service;

use Exception;
use app\common\cache\Cache;

class KeywordMatching
{
    private $key_prefix = 'keyword_matching:';

    public function keyword_matching($content,$data)
    {
        try {
            $keywordRecordService = new KeywordRecordService();
            $keywordManageService = new KeywordManageService();

            $keyword_list = $keywordManageService->getKeywords($data['channel_id']);
            if (empty($keyword_list))
            {
                return false;
            }

            $keywords = array_column($keyword_list, 'keyword');
            $array_count = array_count_values($keywords);
            $temp = join('|', $keywords);
            $patten = '/' . $temp . '/i';
            $count = preg_match_all($patten, $content, $matchs);
            $index = 0;

            if (empty($count))
            {
                return false;
            }

            foreach ($matchs[0] as $match) {
                $index = 0;
                foreach ($keyword_list as $list) {
                    $match = strtolower($match);
                    if ($match == $list['keyword']) {
                        $key = $this->key_prefix . $data['channel_id'] . ':' . $data['message_type'] . ':' . $data['message_id'] . ':' . $match;
                        if (Cache::handler()->exists($key)) {
                            $index++;
                            if ($array_count[$list['keyword']] <= $index) {
                                Cache::handler()->del($key);
                            }
                            $data['auto_reply'] = 1;
                        } else {
                            $data['auto_reply'] = 0;
                        }
                        $data['message_keyword_id'] = $list['id'];
                        $keywordRecordService->save_message($data);
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }
}