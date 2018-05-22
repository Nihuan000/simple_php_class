<?php

/**
 * Created by PhpStorm.
 * User: nihuan
 * Date: 17-12-21
 * Time: 上午10:01
 * Desc: 统计数据导出到excel通用类
 */
require dirname(__FILE__) . '/PHPExcel/Classes/PHPExcel.php';
class outputExcel
{
    /**
     * 列表导出
     * @Author Nihuan
     * @Version 1.0
     * @Date 17-11-27
     * @param $tag_list
     * @param $key_map
     * @param $filename
     * @throws
     */
    public function outputData($tag_list,$key_map = [],$filename = ''){
        $PHPExcel = new PHPExcel();
        $fileName = $filename;
        //设置基本信息
        $PHPExcel->getProperties()->setCreator("Soubu")
            ->setLastModifiedBy("Soubu")
            ->setTitle($fileName)
            ->setSubject($fileName)
            ->setDescription("")
            ->setKeywords($fileName)
            ->setCategory("");
        $PHPExcel->setActiveSheetIndex(0);
        $PHPExcel->getActiveSheet()->setTitle($fileName);

        //填入表头
        $count = count($key_map);
        $keyMapList = self::_setColumnKey($count);
        $fieldList = array_values($key_map);
        foreach ($keyMapList as $key => $val){
            $PHPExcel->getActiveSheet()->setCellValue($val . '1', $fieldList[$key]);
        }
        foreach($tag_list as $k => $v){
            $num=$k+2;
            $column = 0;
            foreach ($v as $fk => $fv){
                $PHPExcel->setActiveSheetIndex(0)->setCellValue($keyMapList[$column].$num, $fv);
                $column ++ ;
            }
            self::_saveExcel($PHPExcel,$fileName);
        }
    }


    /**
     * 设置列名
     * @Author Nihuan
     * @Version 1.0
     * @Date 17-12-21
     * @param $keyCount
     * @return array
     */
    private function _setColumnKey($keyCount){
        $mapping = [];
        $simpleMap = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
        $simpleCount = count($simpleMap);
        $nextCount = $thirdCount = 0;
        if($keyCount > $simpleCount){
            $nextCount = $keyCount - $simpleCount;
            $keyCount = $simpleCount;
            if($nextCount > $simpleCount){
                $thirdCount = $nextCount - $simpleCount;
            }
        }
        if($thirdCount > $simpleCount){
            exit('表格字段超限,最大只能到BZ');
        }
        for($i=0;$i<$keyCount;$i++){
            $mapping[] = $simpleMap[$i];
        }
        if($nextCount > 0){
            $nextKey = $simpleMap[0];
            for ($j = 0;$j<$nextCount;$j++){
                $mapping[] = $nextKey . $simpleMap[$j];
            }
        }

        if($thirdCount > 0){
            $thirdKey = $simpleMap[1];
            for ($k = 0;$k<$thirdCount;$k++){
                $mapping[] = $thirdKey . $simpleMap[$k];
            }
        }
        return $mapping;
    }

    /**
     * 到处excel数据
     * @Author Nihuan
     * @Version 1.0
     * @Date 17-04-17
     * @param $PHPExcel
     * @param $fileName
     * @throws
     */
    private function _saveExcel($PHPExcel,$fileName){
        //保存为2003格式
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition: attachment; filename="' . $fileName . '.xlsx"');
        header("Content-Transfer-Encoding:binary");
        $objWriter = new PHPExcel_Writer_Excel5($PHPExcel);
        $objWriter->save('/srv/Document/' . $fileName . '.xlsx');
    }
}
