<?php


namespace app\service;


use app\ResultTrait;

class PdfService
{
    use ResultTrait;

    /**
     * @param array $company_list  企业信息列表
     */
    public function company_pdf($company_list)
    {
        //引入TCPDF类。 P竖向（默认） L横向
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetCreator('Helloweba');
        $pdf->SetAuthor('福布云商数据中心');
        $pdf->SetTitle('企业信息');
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, PHP');

        // $PDF_MARGIN_TOP = 30;//留给页眉的空间

        // $pdf->SetMargins(20, $PDF_MARGIN_TOP, 20);
        // //设置页头内容
        // $pdf->SetHeaderData(PDF_HEADER_LOGO,PDF_HEADER_LOGO_WIDTH, "福布云商", "https://www.chinafibermarketing.cn");
        // //设置页尾字体和线条颜色
        // $pdf->setFooterData(array(0, 64, 0), array(0, 0, 255));
        // //设置页脚空间
        // $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        //设置字体大小
        $pdf->setFontSize(8);
        
        //添加页面
        $pdf->AddPage();
        //去除页眉
        $pdf->setPrintHeader(false);
        //去除页脚
        $pdf->setPrintFooter(false);
        
        //$pdf->Write(0, '福布云商数据中心，企业数据支持', '', 0, 'C', true, 0, false, false, 0);
        $tbl ="<table border=\"1\" cellspacing=\"0\" cellpadding=\"0\" width=\"800\" style=\"table-layout:fixed;word-wrap:break-word;\">
                    <tr style=\"height:30px\">
                        <th style=\"width:30px;vertical-align: middle;text-align: center;font-weight: bolder\">序号</th>
                        <th style=\"width:150px;vertical-align: middle;text-align: center;font-weight: bolder\">公司名称</th>
                        <th style=\"width:50px;vertical-align: middle;text-align: center;font-weight: bolder\">法人名称</th>
                        <th style=\"width:60px;vertical-align: middle;text-align: center;font-weight: bolder\">注册资金</th>
                        <th style=\"width:80px;vertical-align: middle;text-align: center;font-weight: bolder\">联系电话</th>
                        <th style=\"width:150px;vertical-align: middle;text-align: center;font-weight: bolder\">传真</th>
                        <th style=\"width:100px;vertical-align: middle;text-align: center;font-weight: bolder\">电子邮件1</th>
                        <th style=\"width:150px;vertical-align: middle;text-align: center;font-weight: bolder\">电子邮件2</th>
                    </tr>";
        $content="";
        $i=1;
        foreach ($company_list as $key=>$item){
            $content.=" <tr style=\"height:30px\">
                            <td style=\"vertical-align: middle;text-align: center\">".$i++."</td>
                            <td style=\"vertical-align: middle;text-align: center\">".$item['company_name']."</td>
                            <td style=\"vertical-align: middle;text-align: center\">".$item['oper_name']."</td>
                            <td style=\"vertical-align: middle;text-align: center\">".$item['register_capi']."</td>
                            <td style=\"vertical-align: middle;text-align: center\">".$item['phone']."</td>
                            <td style=\"vertical-align: middle;text-align: center\">".$item['more_phone']."</td>
                            <td style=\"vertical-align: middle;text-align: center\">".$item['email']."</td>
                            <td style=\"vertical-align: middle;text-align: center\">".$item['more_email']."</td>
                        </tr>";
        }
        $html   =   $tbl.$content."</table>";
        $pdf->writeHTMLCell(700, 0, 10, 27, $html, 0, 1, 0, true, 'C', true);
        //$pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Output('t.pdf', 'D');
    }
}