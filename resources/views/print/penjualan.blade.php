<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Penjualan</title>
</head>
<style>
    body {

        font-size: 10pt;
        font-family: 'Calibri';
        line-height: 10px;
        font-weight: 400;
        margin: 0px;
        padding: 0px;

        background-color: #FAFAFA;
    }

    * {
        box-sizing: border-box;
        -moz-box-sizing: border-box;
    }


    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        margin: 0px;
        font-size: 12pt;
        font-weight: bold;
        font-family: 'Calibri';
        color: inherit;
        text-rendering: optimizelegibility;
        padding: 0px 23px;
    }

    .headJudulLaporan {
        text-align: center;
        margin-bottom: 5px;
        /*border-bottom: 1px solid black;*/
    }

    .text-center {
        text-align: center;
    }

    .text-right {
        text-align: right;
    }

    .text-left {
        text-align: left;
    }

    table {

        width: 100%;
        padding: 0px;
        margin: 0px;
        /*border-right: .5px solid #e0e0e0;*/

    }

    table th {
        padding: 10px 10px;
        border-left: .5px solid #e0e0e0;
        border-bottom: 1px solid #e0e0e0;
        border-top: 1px solid #e0e0e0;
        /*border-right: .5px solid #e0e0e0;*/
        background: #ededed;
    }



    table td {
        padding: 0px 24px;
    }

    p {
        margin-bottom: 0px !important;

    }

    @page {
        margin: 0
    }

    .sheet {
        margin: 0mm;
        overflow: hidden;
        position: relative;
        box-sizing: border-box;
        page-break-after: always;
    }

    /** For screen preview **/
    @media screen {
        body {
            background: #e0e0e0
        }

        .sheet {
            width: 76mm;
            height: 100mm;
            background: white;
            box-shadow: 0 .5mm 2mm rgba(0, 0, 0, .3);
            margin: 0mm auto;
        }
    }

    .fs-8 {
        font-size: 8pt;
    }

    body.sheet {
        width: 76mm;
        height: 100mm
    }

    /* change height as you like */
    @media print {
        body.sheet {
            width: 76mm
        }
    }

    /* this line is needed for fixing Chrome's bug */
</style>

<body id="layoutx">
    <div class="sheet">
        <div class="headJudulLaporan">
            <h2><b>TOKO YAUMI</b></h2>
            <p class="text-center" style="margin-top: 2px;">Alamat</p>
            <p class="text-center" style="margin-top: 2px;">Kota Pobolinggo, Telp: telp </p>
            <p class="text-center" style="margin-top: 0px;">-------------------------------------</p>
        </div>
        <div style="margin-top: 5px;">
            <table>
                <tr>
                    <td>{{$invoice}}</td>
                    <td class="text-right">Ksr: {{ $petugas }}</td>
                </tr>
            </table>
            <p class="text-center" style="margin-top: 2px;">============================================== </p>
            <table>
                <tr>
                    <td class="">DETAILS</td>
                    <td class="text-right">HARGA </td>
                </tr>
            </table>
            <p class="text-center" style="margin-top: 2px;">============================================== </p>
            <table>
                @foreach($details as $item)
                <tr>
                    <td colspan="2"> {{$item->product->nama}} </td>
                </tr>
                <tr>
                    <td class="">{{$item->qty}} x {{$item->harga}} </td>
                    <td class="text-right">{{$item->subtotal}}</td>
                </tr>
                @endforeach
            </table>

            <p class="text-center" style="margin-top: 0px;">-------------------------------------------------------------------</p>
            <table>
                <tr>
                    <td class="text-right">TOTAL HARGA :</td>
                    <td class="text-right">{{number_format($total, 0, ',', '.')}}</td>
                </tr>
                <tr>
                    <td class="text-right"> TUNAI :</td>
                    <td class="text-right">{{number_format($bayar, 0, ',', '.')}}</td>
                </tr>
                <tr>
                    <td class="text-right"> KEMBALI :</td>
                    <td class="text-right">{{number_format($kembali, 0, ',', '.')}}</td>
                </tr>

            </table>
            <p class="text-center" style="margin-top: 0px;">============================================== </p>
            <table>
                <tr>
                    <td class="text-center"> {{ $tanggal }}</td>
                </tr>
            </table>
            <p class="text-center" style="margin-top: 0px;">============================================== </p>

        </div>
        <div class="headJudulLaporan">
            <p class="text-center" style="margin-top: 2px;">brg yg sdh dibeli tdk dapat dikembalikan</p>
            <p class="text-center" style="margin-top: 2px;">Terimaksih Atas kunjungan Anda</p>
            <p style="line-height: 10px; font-size: 7.8pt;">.</p>
        </div>

    </div>
</body>

<script type="text/javascript">
    let url = window.location.href
    let split = url.split('?')
    let _newurl = split[0].replace('/print', '')
    // console.log(_newurl)
    myPrinting();


    function afterPrint() {

        let r = confirm("Press a button!");
        if (r == true) {
            window.close();
        } else {
            window.close();
        }
    }


    function myPrinting() {
        window.print();
        setTimeout(function() {
            afterPrint()
        }, 500);
    }

    // console.log(window.onafterprint)
</script>

</html>
