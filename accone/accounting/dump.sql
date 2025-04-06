select 
    SsPiutangPayment as total,
    concat(DATE_FORMAT(T_OrderHeaderDate,'%d%m%Y'),'|',M_CompanyNumber) as TxNo, 
    DATE_FORMAT(T_OrderHeaderDate,'%Y/%m/%d') as ArDate, 
    M_PaymentTypeName as TipeBayar,
    concat(M_PaymentTypeName,'|' , M_CompanyName , '|', ifnull(Nat_BankCode,''), ' ' , ifnull(M_BankAccountNo,'')) as M_PaymentTypeName,
    concat( ifnull(Nat_BankCode,''), ' ' , ifnull(M_BankAccountNo,''))as Account, 
    ifnull(M_BankAccountNo,'') as M_BankAccountNo, 
    M_PaymentTypeID, Nat_BankCode,
    M_OmzetTypeName, M_OmzetTypeID, M_CompanyName,M_CompanyNumber, M_CompanyID,
    Payment_RkF_PaymentNumber, Payment_RkM_BranchCode, Payment_RkAmount
from ss_piutang
    join t_orderheader on SsPiutangT_OrderHeaderID = T_OrderHeaderID AND T_OrderHeaderIsActive = 'Y'
    left join ss_piutang_payment on SsPiutangPaymentSsPiutangID = SsPiutangID

    left join f_bill_issue_pusat on T_OrderHeaderM_CompanyID = F_BillIssuePusatM_CompanyID
    join f_payment_pusat on F_BillPaymentPusatF_BillIssuePusatID = F_BillIssuePusatID
    JOIN f_bill_payment_pusat ON Payment_RkF_BillPaymentPusatID = F_BillPaymentPusatID
   
    left join m_paymenttype on SsPiutangPaymentM_PaymentTypeID = M_PaymentTypeID
    left join m_company on T_OrderHeaderM_CompanyID = M_CompanyID  and M_CompanyIsActive = 'Y'
    left join m_companytype on  M_CompanyM_CompanyTypeID =  M_CompanyTypeID and M_CompanyTypeIsActive = 'Y'
    left join m_mou on T_OrderHeaderM_MouID  = M_MouID
    left join m_omzettype on M_MouM_OmzetTypeID =  M_OmzetTypeID
    left join   t_orderheaderaddon on T_OrderHeaderAddOnT_OrderHeaderID =T_OrderHeaderID 
    left join m_bank_account on SsPiutangPaymentM_BankAccountID = M_BankAccountID
    left join  nat_bank on Nat_BankID = M_BankAccountNat_BankID
    
    where SsPiutangDate =  ?    
    AND M_PaymentTypeID = 20
    and SsPiutangType IN ('B2','A3')    and T_OrderHeaderAddOnIsKaPus = 'N' and SsPiutangIsActive = 'Y'       
    and M_OmzetTypeID <> 7
    group by SsPiutangID,T_OrderHeaderID  