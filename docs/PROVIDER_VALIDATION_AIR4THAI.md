# Air4Thai validation

Status: `VERIFIED LIVE` on 2026-09-02.

- Current endpoint returned HTTP 200 JSON and both allowlisted stations.
- `36t`: Yupparaj Wittayalai School, `18.7909333, 98.9900000`.
- `35t`: Chiang Mai City Hall, `18.8407320, 98.9697800`.
- Official history returned 739 hourly values per station in the validation window; deployment backfill stored 1,480 normalized rows before latest corrections.
- Units: PM2.5/PM10 `µg/m³`, O₃/NO₂/SO₂ `ppb`, CO `ppm`; times interpreted in Asia/Bangkok and stored UTC.
- Strict TLS succeeded using the local official issuer directory. SHA-256 fingerprints: YR1 `13:94:96:34:D9:9C:D6:FD:6A:A8:0B:C0:34:FE:FA:CC:EB:19:69:FE:EF:98:65:86:71:3E:CD:BB:05:75:8D:3F`; YR2 `23:8B:85:A0:09:9C:65:B9:70:47:7D:57:24:F1:A1:D4:75:CE:50:58:CF:FE:4E:FA:87:33:89:9B:DB:86:3C:47`; YR3 `35:50:0C:B6:3B:8D:97:69:52:8D:65:D8:72:20:F5:1C:06:98:07:A5:95:AC:BF:2F:B6:EC:A3:8F:BE:0A:4B:D2`.
