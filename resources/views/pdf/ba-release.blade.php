<!DOCTYPE html>
<html>

<head>
    <title>Berita Acara Penyerahan</title>
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }

        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        .footer {
            margin-top: 50px;
        }

        .signature {
            width: 100%;
        }

        .signature td {
            width: 50%;
            text-align: center;
            height: 100px;
            vertical-align: bottom;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>BERITA ACARA PENYERAHAN DOKUMEN</h2>
        <p>Nomor: {{ $record->ba_number }}</p>
    </div>

    <p>Pada hari ini, tanggal <strong>{{ $record->release_date->format('d/m/Y') }}</strong>, kami yang bertanda tangan
        di bawah ini telah melakukan serah terima dokumen agunan/kredit kepada:</p>

    <table>
        <tr>
            <td>Nama Penerima</td>
            <td>: {{ $record->receiver_name }}</td>
        </tr>
        <tr>
            <td>NIK / No. Identitas</td>
            <td>: {{ $record->receiver_id_number ?? '-' }}</td>
        </tr>
        <tr>
            <td>Keterangan</td>
            <td>: {{ $record->notes }}</td>
        </tr>
    </table>

    <p>Dengan rincian dokumen sebagai berikut:</p>

    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Jenis Dokumen</th>
                <th>Nomor Dokumen</th>
                <th>Nama Nasabah (Debitur)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record->documents as $index => $doc)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $doc->document_type->name }}</td>
                <td>{{ $doc->document_number }}</td>
                <td>{{ $doc->loan->debtor_name }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <table class="signature">
            <tr>
                <td>Pihak Yang Menerima,</td>
                <td>Petugas Bank,</td>
            </tr>
            <tr>
                <td>( {{ $record->receiver_name }} )</td>
                <td>( {{ $record->user->name }} )</td>
            </tr>
        </table>
    </div>
</body>

</html>