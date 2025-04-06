## Prm yg dibutuhin savefisik()

```
    action
    act
    token
    trx[orderid]
    trx['re_id']
    riwayats[]
    fisiks[]
    umum_saran[]
    k3s[]

```

```
{
    "act": "Fisik Umum K3",
    "action": "save",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJNX1VzZXJJRCI6IjIiLCJNX1VzZXJFbWFpbCI6Impva29AZ21haWwuY29tIiwiTV9Vc2VyVXNlcm5hbWUiOiJqb2tvQGdtYWlsLmNvbSIsIk1fVXNlckdyb3VwRGFzaGJvYXJkIjoib25lLXVpXC90ZXN0XC92dWV4XC9vbmUtcGF0aWVudC1saXN0LWJhcmNvZGUtdnYtNi1jcG9uZVwvIiwiTV9Vc2VyRGVmYXVsdFRfU2FtcGxlU3RhdGlvbklEIjoiMSIsIk1fU3RhZmZOYW1lIjoiUEVUVUdBUyBTQU1QTEUgTEFCIiwiaXNfY291cmllciI6Ik4iLCJ0aW1lX2F1dG9sb2dvdXQiOiI1IiwiaXAiOiIxMzkuMTkyLjE2Mi4xNzUiLCJhZ2VudCI6Ik1vemlsbGFcLzUuMCAoV2luZG93cyBOVCAxMC4wOyBXaW42NDsgeDY0KSBBcHBsZVdlYktpdFwvNTM3LjM2IChLSFRNTCwgbGlrZSBHZWNrbykgQ2hyb21lXC8xMzEuMC4wLjAgU2FmYXJpXC81MzcuMzYgRWRnXC8xMzEuMC4wLjAiLCJ2ZXJzaW9uIjoidjIiLCJsYXN0LWxvZ2luIjoiMjAyNS0wMS0wNiAwOToyMTo0NyJ9.DPrsGSYjfS7YYlIDM-o9L7j0Zz4OZUeDlrjdlk8ez0A",
    "trx":{
        "re_id": "867",
        "orderid": "315"
    }
}

```

param get detail

```
    're_id', // So_ResultEntryID
    "T_SamplingSOID", // T_SamplingSoID
```

```
{
    "trx_id": "315",
    "re_id": "867",
    "ordernumber": "R2407140012",
    "ordernumber_ext": "",
    "patient_fullname": "Bpk PAISAL",
    "sexname": "LAKI-LAKI",
    "sexcode": "L",
    "orderdate": "14-07-2024",
    "dob": "06-03-1972",
    "age": "52 tahun 4 bulan 8 hari",
    "umur": "52 TAHUN 4 BULAN 8 HARI",
    "languange_name": "",
    "test_name": "Pemeriksaan Fisik",
    "group_name": "Pemeriksaan Fisik",
    "group_resume_mcu": "FISIK",
    "details": [
        {
            "trx_id": "867",
            "re_id": "867",
            "orderid": "315",
            "sampletypeid": "219",
            "T_SamplingSoID": "1947",
            "test_name": "PEMERIKSAAN FISIK",
            "group_name": "Pemeriksaan Fisik",
            "group_resume_mcu": "FISIK",
            "test_id": "2562",
            "nat_testid": "6236",
            "language_id": "1",
            "template_id": "27",
            "template_name": "Fisik Umum K3",
            "template_flag_other": "Y",
            "status_result": [],
            "status_result_arr": [
                {
                    "id": "1",
                    "name": "Normal",
                    "isNormal": "Y"
                },
                {
                    "id": "2",
                    "name": "Tidak Normal",
                    "isNormal": "N"
                }
            ],
            "status_name": "BARU",
            "note": "",
            "status": "NEW",
            "language_name": "Bahasa Indonesia",
            "doctors": "",
            "doctor_id": "60",
            "doctor_fullname": "dr. Corry RosianaSp.PKSp.PK",
            "details": [],
            "langs": "",
            "photos": [],
            "act": "Fisik Umum K3",
            "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJNX1VzZXJJRCI6IjIiLCJNX1VzZXJFbWFpbCI6Impva29AZ21haWwuY29tIiwiTV9Vc2VyVXNlcm5hbWUiOiJqb2tvQGdtYWlsLmNvbSIsIk1fVXNlckdyb3VwRGFzaGJvYXJkIjoib25lLXVpXC90ZXN0XC92dWV4XC9jcG9uZS1yZXN1bHRlbnRyeS1zby1vdGhlcnMtdjZcLyIsIk1fVXNlckRlZmF1bHRUX1NhbXBsZVN0YXRpb25JRCI6IjEiLCJNX1N0YWZmTmFtZSI6IlBFVFVHQVMgU0FNUExFIExBQiIsImlzX2NvdXJpZXIiOiJOIiwidGltZV9hdXRvbG9nb3V0IjoiNSIsImlwIjoiMTM5LjE5Mi4xNjkuMjA4IiwiYWdlbnQiOiJNb3ppbGxhXC81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NCkgQXBwbGVXZWJLaXRcLzUzNy4zNiAoS0hUTUwsIGxpa2UgR2Vja28pIENocm9tZVwvMTMxLjAuMC4wIFNhZmFyaVwvNTM3LjM2IEVkZ1wvMTMxLjAuMC4wIiwidmVyc2lvbiI6InYyIiwibGFzdC1sb2dpbiI6IjIwMjUtMDEtMDMgMDk6MjU6MzAifQ.Z91YOPJ7VcGNfUqbdgkMLaHWx6ElMBpijd_4o0k1znE"
        }
    ],
    "status_name": "BARU",
    "deliveries": "",
    "iscito": "N",
    "doctor_fullname": "",
    "fo_note": "",
    "fo_note_user": "PETUGAS SAMPLE LAB",
    "fo_ver_note": "",
    "fo_ver_note_user": "",
    "sampling_note": "",
    "sampling_note_user": "",
    "company_name": "PT ASTRA DAIHATSU MOTOR",
    "company_id": "46",
    "T_SamplingSOID": "1947",
    "image_signature": "",
    "T_OrderHeaderID": "315",
    "T_TestID": "2562",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJNX1VzZXJJRCI6IjIiLCJNX1VzZXJFbWFpbCI6Impva29AZ21haWwuY29tIiwiTV9Vc2VyVXNlcm5hbWUiOiJqb2tvQGdtYWlsLmNvbSIsIk1fVXNlckdyb3VwRGFzaGJvYXJkIjoib25lLXVpXC90ZXN0XC92dWV4XC9jcG9uZS1yZXN1bHRlbnRyeS1zby1vdGhlcnMtdjZcLyIsIk1fVXNlckRlZmF1bHRUX1NhbXBsZVN0YXRpb25JRCI6IjEiLCJNX1N0YWZmTmFtZSI6IlBFVFVHQVMgU0FNUExFIExBQiIsImlzX2NvdXJpZXIiOiJOIiwidGltZV9hdXRvbG9nb3V0IjoiNSIsImlwIjoiMTM5LjE5Mi4xNjkuMjA4IiwiYWdlbnQiOiJNb3ppbGxhXC81LjAgKFdpbmRvd3MgTlQgMTAuMDsgV2luNjQ7IHg2NCkgQXBwbGVXZWJLaXRcLzUzNy4zNiAoS0hUTUwsIGxpa2UgR2Vja28pIENocm9tZVwvMTMxLjAuMC4wIFNhZmFyaVwvNTM3LjM2IEVkZ1wvMTMxLjAuMC4wIiwidmVyc2lvbiI6InYyIiwibGFzdC1sb2dpbiI6IjIwMjUtMDEtMDMgMDk6MjU6MzAifQ.Z91YOPJ7VcGNfUqbdgkMLaHWx6ElMBpijd_4o0k1znE"
}
```


Yang dibutuhkan diambil dari parameter form
re_id // So_ResultEntryID
act
action
orderid // 
