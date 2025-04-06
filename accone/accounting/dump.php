// * Jika $rawDbt->Total_RkAmount > 0, dititipin
            else if ($rawDbt->Total_RkAmount > 0) {
                // 1. Kredit pakai coa RK
                $sql = "SELECT Map_RkCabang_CoaAccNo, Map_RkCabang_CoaDesc, Map_RkCabang_BranchCode, coaID
                FROM map_RkCabang
                JOIN coa ON Map_RkCabang_CoaAccNo = coaAccountNo 
                    WHERE Map_RkCabang_BranchCode = ? 
                    AND Map_RkCabang_Type = 'ARPAY'
                    AND Map_RkCabang_IsActive = 'Y'";
                $qry = $this->db->query($sql, [$raw->BranchCode]);
                if (!$qry) {
                    $error = json_encode($this->db->error(), JSON_PRETTY_PRINT);
                    $errMap[] = [
                        'err_type' => 'Mapping',
                        'err_msg' => "Failed mapping coa RK Ar Payment. Ref: {$raw->Ref} | Error DB: {$error}"
                    ];
                    continue;
                }
                if ($qry->row() == null) {
                    $errMap[] = [
                        'err_type' => 'Mapping',
                        'err_msg' => "CoA RK tidak ditemukan. Ref: {$raw->Ref}"
                    ];
                    continue;
                }
                $rawCr->Debit = 0;
                $rawCr->coaID = $qry->row()->coaID;
                $rawCr->coaDescription = $qry->row()->Map_RkCabang_CoaDesc;
                $rawCr->coaAccNo = $qry->row()->Map_RkCabang_CoaAccNo;

                // 2. Debit pakai coa Bank 
                // brati dari cabang harus bawa bank dan norek