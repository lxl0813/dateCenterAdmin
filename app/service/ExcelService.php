<?php


namespace app\service;

use app\model\SharesDatasModel;
use app\model\SharesModel;
use app\model\SharesLogsModel;
use app\ResultTrait;
use think\facade\Cookie;
use think\facade\Db;
use app\model\FinanceWaterModel;

class ExcelService
{
    use ResultTrait;

    /**
     * @param string $excel_path 文件路径
     * @param string $create_time 创建时间
     * @param int $out 去除数
     * @param int $group 分组，单组数量
     * 股票基本信息读取导入数据库
     */
    public function shares_excel_read_to_mysql($excel_path, $create_time, $out = 0, $group = 11)
    {
        $path = $excel_path;
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension == 'xlsx') {
            $reader = new \PHPExcel_Reader_Excel2007();
        } else {
            $reader = new \PHPExcel_Reader_Excel5();
        }
        $excel = $reader->load($path);
        $sheetNames = $excel->getSheetNames();
        $SheetName = $sheetNames[0];
        //根据表名切换当前工作表
        $excel->setActiveSheetIndexByName($SheetName);
        //得到当前工作表对象
        $curSheet = $excel->getActiveSheet();
        //获取当前工作表最大行数
        $rows = $curSheet->getHighestRow();
        $cols = 'L';
        for ($j = 2; $j <= $rows; $j++) {
            for ($k = 'B'; $k <= $cols; $k++) {
                $key = $k . $j;
                $value = $curSheet->getCell($key)->getValue();
                $data[$key] = $value;
            }
        }
        //去除数组前33个并且按照11个分为一组
        $data = array_chunk(array_slice($data, $out), $group);
        //导入人信息
        $create_by = json_decode(Cookie::get('DATACENTER_ADMIN'), true)['id'];
        $create_account = json_decode(Cookie::get('DATACENTER_ADMIN'), true)['admin_account'];
        $create_admin = json_decode(Cookie::get('DATACENTER_ADMIN'), true)['admin_name'];

        foreach ($data as $key => $item) {
            $sheet_data[$key]['shares_code'] = (int)$item['0'];
            $sheet_data[$key]['shares_name'] = $item['1'];
            if ($item['2'] > 1) {
                $sheet_data[$key]['a_probability'] = 1;
            } else {
                $sheet_data[$key]['a_probability'] = $item['2'];
            }

            if ($item['3'] > 1) {
                $sheet_data[$key]['y_probability'] = 1;
            } else {
                $sheet_data[$key]['y_probability'] = $item['3'];
            }
            if ($item['4'] > 1) {
                $sheet_data[$key]['x_probability'] = 1;
            } else {
                $sheet_data[$key]['x_probability'] = $item['4'];
            }
            $sheet_data[$key]['a_sqrt'] = $item['5'];
            $sheet_data[$key]['a_avg'] = $item['6'];
            $sheet_data[$key]['y_sqrt'] = $item['7'];
            $sheet_data[$key]['y_avg'] = $item['8'];
            $sheet_data[$key]['x_sqrt'] = $item['9'];
            $sheet_data[$key]['x_avg'] = $item['10'];
            $sheet_data[$key]['create_time'] = $create_time;
            $sheet_data[$key]['create_by'] = $create_by;
            $sheet_data[$key]['create_account'] = $create_account;
            $sheet_data[$key]['create_admin'] = $create_admin;
        }
        $shares_lists = [
            'create_time' => $create_time,
            'create_by' => $create_by,
            'create_account' => $create_account,
            'create_admin' => $create_admin,
            'rows' => $rows - 3,
        ];
        unset($data);
        //进行入库
        // 启动事务
        Db::startTrans();
        try {
            // $shares_id    =   Db::table("shares")->insertGetId($shares_lists);
            $shares_id = SharesModel::insertGetId($shares_lists);
            foreach ($sheet_data as $key => $item) {
                $sheet_data[$key]['shares_id'] = $shares_id;
            }
            $shares_id = SharesModel::find($shares_id);
            $shares_id->shares()->insertAll($sheet_data);
            // 提交事务
            Db::commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $log = [
                'log' => $e->getMessage(),
                'is_local' => 1,
                'create_time' => date('Y-m-d H:i:s', time()),
            ];
            SharesLogsModel::insert($log);
            return $e->getMessage();
        }
    }


    /**
     * @param array $result 股票数据
     * @param string $fileName 文件名称
     * 股票基本信息导出excel
     */
    public function shares_excel_export($result, $fileName)
    {
        try {
            //创建PHPExcel对象
            $objPHPExcel = new \PHPExcel();
            //定义配置
            //得到当前工作表对象
            $curSheet = $objPHPExcel->setActiveSheetIndex(0);
            //设置sheet的name
            $objPHPExcel->getActiveSheet()->setTitle('股票基本信息表');
            //设置表头
            $curSheet->setCellValue('A1', '序号');
            $curSheet->setCellValue('B1', '股票代码');
            $curSheet->setCellValue('C1', '股票名称');
            $curSheet->setCellValue('D1', 'A概率');
            $curSheet->setCellValue('E1', 'Y概率');
            $curSheet->setCellValue('F1', 'X概率');
            $curSheet->setCellValue('G1', 'A平均根');
            $curSheet->setCellValue('H1', 'A平均值');
            $curSheet->setCellValue('I1', 'Y平均根');
            $curSheet->setCellValue('J1', 'Y平均值');
            $curSheet->setCellValue('K1', 'X平均根');
            $curSheet->setCellValue('L1', 'X平均值');
            //$curSheet->setCellValue('A2','日期：'.$result[0]['create_time']);
            //设置单元格的值
            $column = 2;
            $id = 1;
            foreach ($result as $key => $item) { // 行写入
                $span = ord("A");
                unset($item['create_time']);
                foreach ($item as $keyName => $value) { // 列写入
                    if (chr($span) == "A") {
                        $curSheet->setCellValue(chr($span) . $column, $id);
                    } else {
                        $curSheet->setCellValue(chr($span) . $column, $value);
                    }
                    $span++;
                }
                $id++;
                $column++;
            }
            //激活当前表
            $objPHPExcel->setActiveSheetIndex(0);
            ob_end_clean();//清除缓冲区,避免乱码
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $dir = "../public/storage/shares_base_export/" . date('Ymd') . '/';
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $url = $dir . $fileName . ".xlsx";
            $objWriter->save($url);
            $excel_urls = "http://www.shares.com/" . substr($url, 9);
        } catch (\Exception $e) {
            return false;
        }
        return $excel_urls;

    }


    /**
     * @param array $shares_base 股票基本信息
     * @param array $shares_more 股票更多信息
     * @param array|int $shares_cv 股票CV值
     */
    public function shares_details_export(array $shares_base, array $shares_more, $shares_cv)
    {
        $fileName = $shares_base[0]['create_time'] . "股票CV详情";
        try {
            //创建PHPExcel对象
            $objPHPExcel = new \PHPExcel();
            //得到当前工作表对象
            $curSheet = $objPHPExcel->setActiveSheetIndex(0);
            //设置sheet的name
            $objPHPExcel->getActiveSheet()->setTitle('股票基本信息表');
            //设置表头
            $curSheet->setCellValue('A1', '序号');
            $curSheet->setCellValue('B1', '股票代码');
            $curSheet->setCellValue('C1', '股票名称');
            $curSheet->setCellValue('D1', 'A概率');
            $curSheet->setCellValue('E1', 'Y概率');
            $curSheet->setCellValue('F1', 'X概率');
            $curSheet->setCellValue('G1', 'A平均根');
            $curSheet->setCellValue('H1', 'A平均值');
            $curSheet->setCellValue('I1', 'Y平均根');
            $curSheet->setCellValue('J1', 'Y平均值');
            $curSheet->setCellValue('K1', 'X平均根');
            $curSheet->setCellValue('L1', 'X平均值');
            //设置单元格的值
            $column = 2;
            $id = 1;
            foreach ($shares_base as $key => $item) { // 行写入
                $span = ord("A");
                unset($item['create_time']);
                unset($item['shares_id']);
                unset($item['create_by']);
                unset($item['create_account']);
                unset($item['create_admin']);
                foreach ($item as $keyName => $value) { // 列写入
                    if (chr($span) == "A") {
                        $curSheet->setCellValue(chr($span) . $column, $id);
                    } else {
                        $curSheet->setCellValue(chr($span) . $column, $value);
                    }
                    $span++;
                }
                $id++;
                $column++;
            }
//            //激活当前表
//            $objPHPExcel->setActiveSheetIndex(0);
//            ob_end_clean();//清除缓冲区,避免乱码


            //写第二张表
            $objPHPExcel->createSheet();
            $curSheet = $objPHPExcel->setActiveSheetIndex(1);
            //设置sheet的name
            $objPHPExcel->getActiveSheet()->setTitle('股票更多信息表');
            if (empty($shares_more)) {
                $objPHPExcel->getActiveSheet()->mergeCells('A1:R1');
                $curSheet->setCellValue('A1', '没有符合您设置的三种概率的股票数据，您可以对三种概率进行修改后，再次操作');
            } else {
                //设置表头
                $curSheet->setCellValue('A1', '序号');
                $curSheet->setCellValue('B1', '股票代码');
                $curSheet->setCellValue('C1', '股票名称');
                $curSheet->setCellValue('D1', 'A概率');
                $curSheet->setCellValue('E1', 'Y概率');
                $curSheet->setCellValue('F1', 'X概率');
                $curSheet->setCellValue('G1', 'A平均根');
                $curSheet->setCellValue('H1', 'A平均值');
                $curSheet->setCellValue('I1', 'Y平均根');
                $curSheet->setCellValue('J1', 'Y平均值');
                $curSheet->setCellValue('K1', 'X平均根');
                $curSheet->setCellValue('L1', 'X平均值');
                $curSheet->setCellValue('M1', '交易手数');
                $curSheet->setCellValue('N1', '交易值');
                $curSheet->setCellValue('O1', '交易毛利预测');
                $curSheet->setCellValue('P1', '印花税');
                $curSheet->setCellValue('Q1', '交易净利');
                $curSheet->setCellValue('R1', '基础资金量');
                $curSheet->setCellValue('S1', '交易净值');
                $curSheet->setCellValue('T1', '日盈利');
                //设置单元格的值
                $column = 2;
                $id = 1;
                foreach ($shares_more as $key => $item) { // 行写入
                    $span = ord("A");
                    unset($item['shares_id']);
                    unset($item['create_time']);
                    unset($item['create_by']);
                    unset($item['create_account']);
                    unset($item['create_admin']);
                    foreach ($item as $keyName => $value) { // 列写入
                        if (chr($span) == "A") {
                            $curSheet->setCellValue(chr($span) . $column, $id);
                        } else {
                            $curSheet->setCellValue(chr($span) . $column, $value);
                        }
                        $span++;
                    }
                    $id++;
                    $column++;
                }
            }

            //激活当前表
            $objPHPExcel->setActiveSheetIndex(1);
            ob_end_clean();//清除缓冲区,避免乱码

            //写第二张表
            $objPHPExcel->createSheet();
            $curSheet = $objPHPExcel->setActiveSheetIndex(2);
            //设置sheet的name
            $objPHPExcel->getActiveSheet()->setTitle('股票CV值');
            if (is_array($shares_cv) && empty($shares_cv)) {
                $objPHPExcel->getActiveSheet()->mergeCells('A1:R1');
                $curSheet->setCellValue('A1', '没有符合您设置的三种概率的股票数据来进行CV值的计算，您可以对三种概率进行修改后，再次操作');
            } elseif (!is_array($shares_cv) && $shares_cv == 2) {
                $objPHPExcel->getActiveSheet()->mergeCells('A1:R1');
                $curSheet->setCellValue('A1', '您选择不计算CV值！');
            } else {
                //设置表头
                $curSheet->setCellValue('A1', '序号');
                $curSheet->setCellValue('B1', '股票代码');
                $curSheet->setCellValue('C1', '股票名称');
                foreach ($shares_cv['title'][0] as $key => $item) {
                    if (is_array($item)) {
                        $title[] = $item['create_time'] . "日盈利";
                    } else {
                        $title[] = "CV值";
                    }
                }
                $line = ord("D");
                foreach ($title as $key => $item) {
                    $curSheet->setCellValue(chr($line) . '1', $item);
                    $line++;
                }
                //设置单元格的值
                $column = 2;
                $id = 1;
                foreach ($shares_cv['data'] as $key => $item) { // 行写入
                    $span = ord("A");
                    //var_dump($item);exit;
                    foreach ($item as $keyName => $value) { // 列写入
                        if (chr($span) == "A") {
                            $curSheet->setCellValue(chr($span) . $column, $id);
                        } else {
                            $curSheet->setCellValue(chr($span) . $column, $value);
                        }
                        $span++;
                    }
                    $id++;
                    $column++;
                }
            }
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $dir = "../public/storage/shares_cv_export/" . date('Ymd') . '/';
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $url = $dir . $fileName . ".xlsx";
            $objWriter->save($url);
            $excel_urls = "http://www.shares.com/" . substr($url, 9);
        } catch (\Exception $e) {
            return $e->getMessage();
            return;
        }
        return $excel_urls;
    }


    /**
     * 公司流水导入数据库
     * @param $file_path 文件地址
     * @param $company_water_data 公司流水基本信息
     */
    public function company_water_to_mysql($file_path, $company_water_data)
    {
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        if ($extension == 'xlsx') {
            $reader = new \PHPExcel_Reader_Excel2007();
        } else {
            $reader = new \PHPExcel_Reader_Excel5();
        }
        $excel = $reader->load($file_path);
        $sheetNames = $excel->getSheetNames();
        $SheetName = $sheetNames[0];
        //根据表名切换当前工作表
        $excel->setActiveSheetIndexByName($SheetName);
        //得到当前工作表对象
        $curSheet = $excel->getActiveSheet();
        //获取当前工作表最大行数
        $rows = $curSheet->getHighestRow();
        $cols = 'G';
        for ($j = 2; $j <= $rows; $j++) {
            for ($k = 'A'; $k <= $cols; $k++) {
                $key = $k . $j;
                $value = $curSheet->getCell($key)->getValue();
                $data[$key] = $value;
            }
        }
        $data = array_chunk(array_slice($data, 0), 7);
        $jisu = 0;
        foreach ($data as $k => $v) {
            $jisu = $jisu + 1;
            $water_data[$k]['create_time'] = substr($v[0], 0, 4) . '-' . substr($v[0], 4, 2) . '-' . substr($v[0], 6, 2);
            $water_data[$k]['water_info'] = $v[1];
            $water_data[$k]['water_pay'] = $v[2];
            $water_data[$k]['water_income'] = $v[3];
            $water_data[$k]['account_balance'] = $v[4];
            $water_data[$k]['other_account'] = $v[5];
            $water_data[$k]['other_open_ac_mec'] = $v[6];
            $water_data[$k]['status'] = 2;
            $water_data[$k]['update_time'] = $water_data[$k]['create_time'];
            $water_data[$k]['create_by'] = json_decode(Cookie::get('DATACENTER_ADMIN'), true)['id'];
            $water_data[$k]['create_admin'] = json_decode(Cookie::get('DATACENTER_ADMIN'), true)['admin_name'];
            $water_data[$k]['company_id'] = $company_water_data['company_id'];
            $water_data[$k]['company_name'] = $company_water_data['company_name'];
            $water_data[$k]['add_time'] = date('Y-m-d H:i:s', time() + $jisu);
            $water_data[$k]['update_by'] = $water_data[$k]['create_by'];
            $water_data[$k]['update_admin'] = $water_data[$k]['create_admin'];
            $water_data[$k]['bank_id'] = $company_water_data['company_bank_id'];
            $water_data[$k]['bank_name'] = $company_water_data['company_bank_name'];
        }

        //入库
        $finance_water_model = new FinanceWaterModel();
        try {
            $add_result = $finance_water_model->saveAll($water_data);
            if ($add_result) {
                return true;
            } else {
                return false;
            }
        } catch (\ErrorException $exception) {
            return $this->resultError($extension);
        }

    }
}