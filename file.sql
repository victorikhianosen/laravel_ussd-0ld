DECLARE
    p_customer_created   BOOLEAN := false;
    p_account_created    BOOLEAN := false;
    p_customer_id        NUMBER;
    p_bvn_data           VARCHAR2(4000);
    jo                   json_object_t;
    customer_jo          json_object_t;
    p_details            json_object_t;
    p_bvn_phone          VARCHAR2(20) := NULL;
    f_phone              VARCHAR2(20);
    f_firstname          VARCHAR2(200);
    f_lastname           VARCHAR2(200);
    f_dob                VARCHAR2(20);
    f_customer           fcubs_customer%rowtype;
    f_account            fcubs_account%rowtype;
    f_account_id         NUMBER;
    f_message            VARCHAR2(200);
    p_string             VARCHAR2(4000);
    p_resolved           BOOLEAN := false;
    t_details            json_array_t;
    g_customer           fcubs_customer%rowtype;
    ja                   json_array_t;
    customer_no          VARCHAR2(100);
BEGIN
   --- create customer without bvn
    IF ( :phone IS NULL ) THEN
        helpers.json_response(error_msg.message(402));
        return;
    END IF;
    IF ( :pin IS NULL ) THEN
        helpers.json_response(error_msg.message(404));
        return;
    END IF;
    IF ( fcubs.customer_exists(:phone) ) THEN
        helpers.json_response(error_msg.message(403));
        return;
    END IF;
    f_customer := fcubs.get_customer_with_bvn(:bvn);
    IF ( f_customer.id IS NOT NULL ) THEN
        helpers.json_response(error_msg.message(410));
        return;
    END IF;
        -- find bvn from db or paystack
    p_bvn_data := util_paystack.get_resolved_bvn(:bvn);
    --htp.p(p_bvn_data);return;
    IF ( p_bvn_data IS NULL ) THEN
        p_bvn_data := util_paystack.resolve_bvn(:bvn);
    END IF;
    IF ( p_bvn_data IS NOT NULL ) THEN
        util_paystack.save_resolved_bvn(:bvn, p_bvn_data);
        jo := json_object_t.parse(p_bvn_data);
        -- p_resolved := jo.get_boolean('status');
        IF ( instr(p_bvn_data, 'resolved') >= 1 ) THEN
            -- htp.p('resolved'); return;
            p_details := jo.get_object('data');
            p_bvn_phone := p_details.get_string('mobile');
            --htp.p(p_bvn_phone); htp.p(:phone); return;
        -- bvn must match the phone
            IF ( substr(p_bvn_phone, -10) != substr(:phone, -10) ) THEN
                helpers.json_response(error_msg.message(420));
                return;
            END IF;
            -- set fields with bvn data
            f_firstname := p_details.get_string('first_name');
            f_lastname := p_details.get_string('last_name');
            f_dob := p_details.get_string('formatted_dob');
            -- htp.p(f_lastname); return;
        ELSE
            helpers.json_response(error_msg.message(501, 'Could not resolve BVN'));
            return;
        END IF;
    ELSE
         --- must have entered an invalid bvn 
        helpers.json_response(error_msg.message(501));
        return;
    END IF;
    /*
     create customer
    */
    fcubs.create_customer(p_firstname => f_firstname, p_lastname => f_lastname, p_dob => f_dob, p_phone => :phone, p_pin => :pin
    ,
                          p_mno => :mno, p_gender => 'M', p_bvn => :bvn, p_successful => p_customer_created, p_record_id => p_customer_id
                          );
    -- htp.p(p_customer_id); return;
    -- customer creation may fail and then we have to check if customer exists
    IF ( p_customer_created ) THEN
        jo := json_object_t.parse(error_msg.success_message);
        helpers.json_response(jo.to_string);
        return;
    END IF;
    -- we cant create account
    helpers.json_response(error_msg.message(-1, fcubs.no_response_message));
    return;
    exception when others then 
    helpers.json_response(error_msg.message(-1,fcubs.no_response_message));
END;


-- create a lapo user

-- DECLARE
--     p_customer_created   BOOLEAN := false;
--     p_account_created    BOOLEAN := false;
--     p_customer_id        NUMBER;
-- begin
--  fcubs.create_customer(p_firstname => 'Ezekiel', p_lastname => 'Dare', p_dob => '1995-11-09', p_phone => '09064703871', p_pin => '1994'
--     ,
--                           p_mno => 'mtn', p_gender => 'M', p_bvn => '22210355093', p_successful => p_customer_created, p_record_id => p_customer_id
--                           );
-- end

--  my account response
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:fcub="http://fcubs.ofss.com/service/FCUBSCustomerService" xmlns:xs="http://www.w3.org/2001/XMLSchema">
<soapenv:Header/>
<soapenv:Body>
<fcub:CREATECUSTOMER_FSFS_REQ>
<fcub:FCUBS_HEADER>
<fcub:SOURCE>LAPOUSSD</fcub:SOURCE>
<fcub:UBSCOMP>FCUBS</fcub:UBSCOMP>
<fcub:USERID>LAPOUSSD</fcub:USERID>
<fcub:BRANCH>900</fcub:BRANCH>
<fcub:SERVICE>FCUBSCustomerService</fcub:SERVICE>
<fcub:OPERATION>CreateCustomer</fcub:OPERATION>
</fcub:FCUBS_HEADER>
<fcub:FCUBS_BODY>
<fcub:Customer-Full>
    <fcub:CTYPE>I</fcub:CTYPE>
    <fcub:TRACK_LIMITS>Y</fcub:TRACK_LIMITS>
    <fcub:NAME>Dare Ezekiel</fcub:NAME>
    <fcub:ADDRLN1>LAGOS</fcub:ADDRLN1>
    <fcub:ADDRLN3></fcub:ADDRLN3>
    <fcub:ADDRLN2></fcub:ADDRLN2>
    <fcub:ADDRLN4></fcub:ADDRLN4>
    <fcub:COUNTRY>NG</fcub:COUNTRY>
    <fcub:NLTY>NG</fcub:NLTY>
    <fcub:CCATEG>INDIVIDUAL</fcub:CCATEG>
    <fcub:FULLNAME>Dare Ezekiel</fcub:FULLNAME><fcub:UIDNAME>BVN</fcub:UIDNAME>
            <fcub:UIDVAL>22210355093</fcub:UIDVAL>
    <fcub:MEDIA>MAIL</fcub:MEDIA>
    <fcub:LOC>NG</fcub:LOC>
    <fcub:Custpersonal>
        <fcub:FSTNAME>EZEKIEL</fcub:FSTNAME>
        <fcub:MIDNAME />
        <fcub:LSTNAME>DARE</fcub:LSTNAME>
        <fcub:DOB>1995-11-09</fcub:DOB>
        <fcub:GENDR>M</fcub:GENDR>
        <fcub:NATIONID>NG</fcub:NATIONID>
        <fcub:EMAILID />
        <fcub:MOBNUM>09064703871</fcub:MOBNUM>
        <fcub:LANG>ENG</fcub:LANG>
        <fcub:TITLE>Mr.</fcub:TITLE>
        <fcub:PLACEOFBIRTH></fcub:PLACEOFBIRTH>
    </fcub:Custpersonal>
    <fcub:UDFDETAILS>
        <fcub:FLDNAM>RELATIONSHIP_MANAGER</fcub:FLDNAM>
        <fcub:FLDVAL>LAPOUSSD</fcub:FLDVAL>
    </fcub:UDFDETAILS>
</fcub:Customer-Full>
</fcub:FCUBS_BODY>
</fcub:CREATECUSTOMER_FSFS_REQ>
</soapenv:Body>
</soapenv:Envelope>
SUCCESS
<?xml version="1.0" encoding="UTF-8"?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
  <S:Body>
    <CREATECUSTOMER_FSFS_RES xmlns="http://fcubs.ofss.com/service/FCUBSCustomerService">
      <FCUBS_HEADER>
        <SOURCE>LAPOUSSD</SOURCE>
        <UBSCOMP>FCUBS</UBSCOMP>
        <MSGID>6123041222851415</MSGID>
        <CORRELID>null</CORRELID>
        <USERID>LAPOUSSD</USERID>
        <ENTITY>null</ENTITY>
        <BRANCH>900</BRANCH>
        <MODULEID>ST</MODULEID>
        <SERVICE>FCUBSCustomerService</SERVICE>
        <OPERATION>CreateCustomer</OPERATION>
        <DESTINATION>LAPOUSSD</DESTINATION>
        <FUNCTIONID>STDCIF</FUNCTIONID>
        <ACTION>NEW</ACTION>
        <MSGSTAT>SUCCESS</MSGSTAT>
      </FCUBS_HEADER>
      <FCUBS_BODY>
        <Customer-Full>
          <CUSTNO>006965678</CUSTNO>
          <CTYPE>I</CTYPE>
          <NAME>Dare Ezekiel</NAME>
          <ADDRLN1>LAGOS</ADDRLN1>
          <COUNTRY>NG</COUNTRY>
          <NLTY>NG</NLTY>
          <LBRN>900</LBRN>
          <CCATEG>INDIVIDUAL</CCATEG>
          <FULLNAME>EZEKIEL DARE</FULLNAME>
          <CIFCREATIONDT>2023-02-10</CIFCREATIONDT>
          <AR_AP_TRACKING>N</AR_AP_TRACKING>
          <UIDNAME>BVN</UIDNAME>
          <UIDVAL>22210355093</UIDVAL>
          <FROZEN>N</FROZEN>
          <DEAD>N</DEAD>
          <WHRUNKN>N</WHRUNKN>
          <MEDIA>MAIL</MEDIA>
          <LOC>NG</LOC>
          <MAILRSREQD>N</MAILRSREQD>
          <CLSPARTICIPANT>N</CLSPARTICIPANT>
          <FXNETTCUST>006965678</FXNETTCUST>
          <CRMCUST>N</CRMCUST>
          <TRSRYCUST>N</TRSRYCUST>
          <RELPRICING>N</RELPRICING>
          <MT920>N</MT920>
          <FLGJOINT>N</FLGJOINT>
          <CREATEACC>N</CREATEACC>
          <TRACK_LIMITS>Y</TRACK_LIMITS>
          <LIABID>006965678</LIABID>
          <LMCCY>NGN</LMCCY>
          <FLGUTLTYPRVDR>N</FLGUTLTYPRVDR>
          <AMLREQD>N</AMLREQD>
          <CHKDIGITVALREQD>N</CHKDIGITVALREQD>
          <FTACCASOF>M</FTACCASOF>
          <CUSTUNADV>N</CUSTUNADV>
          <CONSTAXCERTRQD>N</CONSTAXCERTRQD>
          <INDTAXCERTRQD>N</INDTAXCERTRQD>
          <CLSCCYALLWD>D</CLSCCYALLWD>
          <INVESTCUST>N</INVESTCUST>
          <SSTAFF>N</SSTAFF>
          <SPECIAL_CUST>N</SPECIAL_CUST>
          <CRS_CUST_TYPE>I</CRS_CUST_TYPE>
          <MAKER>LAPOUSSD</MAKER>
          <MAKERSTAMP>2023-02-10 09:57:52</MAKERSTAMP>
          <CHECKER>LAPOUSSD</CHECKER>
          <CHECKERSTAMP>2023-02-10 09:57:52</CHECKERSTAMP>
          <MODNO>1</MODNO>
          <TXNSTAT>O</TXNSTAT>
          <AUTHSTAT>A</AUTHSTAT>
          <Custpersonal>
            <FSTNAME>EZEKIEL</FSTNAME>
            <LSTNAME>DARE</LSTNAME>
            <DOB>1995-11-09</DOB>
            <GENDR>M</GENDR>
            <NATIONID>NG</NATIONID>
            <RESSTATUS>R</RESSTATUS>
            <MOBNUM>09064703871</MOBNUM>
            <LANG>ENG</LANG>
            <SBMTAGEPROOF>N</SBMTAGEPROOF>
            <KYCSTAT>N</KYCSTAT>
            <TITLE>Mr.</TITLE>
            <Custdomestic>
              <CUSTNO>006965678</CUSTNO>
            </Custdomestic>
            <Custprof>
              <CUSTNO>006965678</CUSTNO>
            </Custprof>
          </Custpersonal>
          <Custcorp/>
          <Cust-Liab>
            <LIABILITY_NUMBER>006965678</LIABILITY_NUMBER>
            <LIABILTY_NAME>Dare Ezekiel</LIABILTY_NAME>
            <LIAB_BRANCH>900</LIAB_BRANCH>
            <LIAB_CCY>NGN</LIAB_CCY>
          </Cust-Liab>
          <Custmis>
            <CUST>006965678</CUST>
            <CUSTNO>006965678</CUSTNO>
            <BRNCD>900</BRNCD>
          </Custmis>
          <Jointcustomer>
            <CUSTNO>006965678</CUSTNO>
          </Jointcustomer>
          <Cust-Acc-Det>
            <CUSTNO>006965678</CUSTNO>
          </Cust-Acc-Det>
          <Custaccdet>
            <CUSTNO>006965678</CUSTNO>
            <Relationship-Linkage>
              <CUSTOMER>006965678</CUSTOMER>
              <RELATIONSHIP>PRIMARY</RELATIONSHIP>
              <INHERIT>N</INHERIT>
              <DESCP>EZEKIEL DARE</DESCP>
              <CATEGORY>C</CATEGORY>
            </Relationship-Linkage>
            <Reverse-Relationship>
              <INHERIT>N</INHERIT>
              <REF_NO>006965678</REF_NO>
              <RELATIONSHIP>PRIMARY</RELATIONSHIP>
              <BRANCH>900</BRANCH>
              <CUSTOMER>006965678</CUSTOMER>
              <CATEGORY>C</CATEGORY>
              <DESC>EZEKIEL DARE</DESC>
            </Reverse-Relationship>
          </Custaccdet>
          <Custacdetail>
            <CUSTNO>006965678</CUSTNO>
          </Custacdetail>
          <UDFDETAILS>
            <FLDNAM>CUSTOMER_INTRODUCER</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>GENDER</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>INTRODUCER_ADDRESS</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>INTRODUCER_OCCUPATION</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>INTRODUCER_PHONE_NUMBER</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>IDENTIFIER_EXP_DATE</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>IDENTIFIER_ISSUE_COUNTRY</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>IDENTIFIER_ISSUE_DATE</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>IDENTIFIER_AUTHORITY</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>IDENTIFIER_VALUE</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>IDENTIFIER_TYPE</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>REGISTRATION_AUTHORITY</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>INCORPORATION_STATE</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>CUST_ORIGIN_CITY</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>CUST_ORIGIN_LGA</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>CUST_ORIGIN_STATE</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>SOURCEOF_WEALTH</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>RESIDENTPERMIT_CARD_NUMBER</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>RESIDENTPERMIT_EXP_DATE</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>RESIDENTPERMIT_ISSUE_DATE</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>POLITICALLY_EXPOSED_PERSON</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>PEPSTATUS</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>PEP_RELATION</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>PEP_NAME</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>FINANCIALLY_EXPOSED_PERSON</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>NEXTOFKIN_TITLE</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>NEXTOFKIN_FIRSTNAME</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>NEXTOFKIN_SURNAME</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>NEXTOFKIN_ADDRESS_LINE1</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>NEXTOFKIN_CITY</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>NEXTOFKIN_LGA</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>NEXTOFKIN_STATE</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>NEXTOFKIN_COUNTRY</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>NEXTOFKIN_GENDER</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>NEXTOFKIN_RELATIONSHIP</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>NEXTOFKIN_PHONE_NUMBER</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>RC_NUMBER</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>KYC_TIER</FLDNAM>
            <FLDVAL>T1</FLDVAL>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>SOURCE_OF_INCOME</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>RM_CODE</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>CUSTCAT</FLDNAM>
            <FLDVAL>IND</FLDVAL>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>CUSTOMER_RELIGION</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>NEXTOFKIN_DOB</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>NEXTOFKIN_MARITALSTATUS</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>NEXTOFKIN_MIDDLENAME</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>RELATIONSHIP_MANAGER</FLDNAM>
            <FLDVAL>LAPOUSSD</FLDVAL>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>EMPLOYMENT_DATE</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>EMP_NATURE_OF_BUSINESS</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>EMPLOYER_FAX</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>SECTOR</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>AGENT_RM</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>GROUP_CODE</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>EMPLOYER_ADDR1</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>EMPLOYER_ADDR2</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>NEXTOFKIN_EMAIL</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>NEXTOFKIN_MOBILE</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>PEP_POSITION</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>GUARDIAN_RELATION</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>APPROVER_TYPE</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>BANKS_IN_NIGERIA</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>FFZ_TRN_REF</FLDNAM>
          </UDFDETAILS>
          <UDFDETAILS>
            <FLDNAM>TERMINAL_ID</FLDNAM>
          </UDFDETAILS>
          <Master>
            <KEY_ID>006965678</KEY_ID>
            <FUNCTION_ID>STDCIF</FUNCTION_ID>
          </Master>
        </Customer-Full>
        <FCUBS_WARNING_RESP>
          <WARNING>
            <WCODE>ST-CIF-310</WCODE>
            <WDESC>Field Short Name is blank</WDESC>
          </WARNING>
          <WARNING>
            <WCODE>ST-SAVE-002</WCODE>
            <WDESC>Record Successfully Saved and Authorized</WDESC>
          </WARNING>
        </FCUBS_WARNING_RESP>
      </FCUBS_BODY>
    </CREATECUSTOMER_FSFS_RES>
  </S:Body>
</S:Envelope>

<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:fcub="http://fcubs.ofss.com/service/FCUBSAccService" xmlns:xs="http://www.w3.org/2001/XMLSchema">
<soapenv:Header/>
<soapenv:Body>
<fcub:CREATECUSTACC_FSFS_REQ>
<fcub:FCUBS_HEADER>
<fcub:SOURCE>LAPOUSSD</fcub:SOURCE>
<fcub:UBSCOMP>FCUBS</fcub:UBSCOMP>
<fcub:USERID>LAPOUSSD</fcub:USERID>
<fcub:BRANCH>900</fcub:BRANCH>
<fcub:SERVICE>FCUBSAccService</fcub:SERVICE>
<fcub:OPERATION>CreateCustAcc</fcub:OPERATION>
</fcub:FCUBS_HEADER>
<fcub:FCUBS_BODY>
<fcub:Cust-Account-Full>
<fcub:BRN>900</fcub:BRN>
<!--  Branch Code where the customer account is to be created -->
<fcub:ACC/>
<!--  The Account number, to be left blank. Account number will be generated in NUBAN format and will be retrieved in the response  -->
<fcub:CUSTNO>006965678</fcub:CUSTNO>
<!--  The customer number  -->
<fcub:ACCLS>VSA1</fcub:ACCLS>
<!--  The CASA product (account class)  -->
<fcub:CCY>NGN</fcub:CCY>
<!--  The Account currency  -->
</fcub:Cust-Account-Full>
</fcub:FCUBS_BODY>
</fcub:CREATECUSTACC_FSFS_REQ>
</soapenv:Body>
</soapenv:Envelope>
URL: http://10.0.0.231:7003/FCUBSAccService/FCUBSAccService?WSDL
<?xml version='1.0' encoding='UTF-8'?><S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/"><S:Body><CREATECUSTACC_FSFS_RES xmlns="http://fcubs.ofss.com/service/FCUBSAccService"><FCUBS_HEADER><SOURCE>LAPOUSSD</SOURCE><UBSCOMP>FCUBS</UBSCOMP><MSGID>6123041187477907</MSGID><CORRELID>null</CORRELID><USERID>LAPOUSSD</USERID><ENTITY>null</ENTITY><BRANCH>900</BRANCH><MODULEID>ST</MODULEID><SERVICE>FCUBSAccService</SERVICE><OPERATION>CreateCustAcc</OPERATION><DESTINATION>LAPOUSSD</DESTINATION><FUNCTIONID>STDCUSAC</FUNCTIONID><ACTION>NEW</ACTION><MSGSTAT>FAILURE</MSGSTAT></FCUBS_HEADER><FCUBS_BODY><Cust-Account-Full><BRN>900</BRN><ACC></ACC><CUSTNO>006965678</CUSTNO><ACCLS>VSA1</ACCLS><CCY>NGN</CCY></Cust-Account-Full><FCUBS_ERROR_RESP><ERROR><ECODE>ST-SAVE-004</ECODE><EDESC>Failed to Save the Record</EDESC></ERROR><ERROR><ECODE>ST-VALS-011</ECODE><EDESC>Invalid Value 006965678 for Field Customer No</EDESC></ERROR></FCUBS_ERROR_RESP></FCUBS_BODY></CREATECUSTACC_FSFS_RES></S:Body></S:Envelope>
FAILURE



{"msisdn":"2347066192100","commandID":"112","content":"1","src":"mtn","serviceCode":"*372","reply":"Select Account:\n1. 0030470707 ","action":"Continue"}

<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:fcub="http://fcubs.ofss.com/service/FCUBSCustServiceCustom">
   <soapenv:Header/>
   <soapenv:Body>
      <fcub:QUERYQUERYCUSTCUSTOM_IOFS_REQ>
         <fcub:FCUBS_HEADER>
            <fcub:SOURCE>LAPOUSSD</fcub:SOURCE>
            <fcub:UBSCOMP>FCUBS</fcub:UBSCOMP>
            <fcub:USERID>LAPOUSSD</fcub:USERID>
            <fcub:BRANCH>900</fcub:BRANCH>
            <fcub:SERVICE>FCUBSCustServiceCustom</fcub:SERVICE>
            <fcub:OPERATION>QueryQueryCustCustom</fcub:OPERATION>
         </fcub:FCUBS_HEADER>
         <fcub:FCUBS_BODY>
            <fcub:Main-IO>
             <fcub:MOBILE_NUMBER>09064703871</fcub:MOBILE_NUMBER>
            </fcub:Main-IO>
         </fcub:FCUBS_BODY>
      </fcub:QUERYQUERYCUSTCUSTOM_IOFS_REQ>
   </soapenv:Body>
</soapenv:Envelope>
Flexcube Endpoint: http://10.0.0.2:9003/FCUBSCustServiceCustom/FCUBSCustServiceCustom?WSDL
<?xml version="1.0" encoding="UTF-8"?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
  <S:Body>
    <QUERYQUERYCUSTCUSTOM_IOFS_RES xmlns="http://fcubs.ofss.com/service/FCUBSCustServiceCustom">
      <FCUBS_HEADER>
        <SOURCE>LAPOUSSD</SOURCE>
        <UBSCOMP>FCUBS</UBSCOMP>
        <MSGID>6123041222851438</MSGID>
        <CORRELID>null</CORRELID>
        <USERID>LAPOUSSD</USERID>
        <ENTITY>null</ENTITY>
        <BRANCH>900</BRANCH>
        <MODULEID>ST</MODULEID>
        <SERVICE>FCUBSCustServiceCustom</SERVICE>
        <OPERATION>QueryQueryCustCustom</OPERATION>
        <DESTINATION>LAPOUSSD</DESTINATION>
        <FUNCTIONID>STDCUSQY</FUNCTIONID>
        <ACTION>EXECUTEQUERY</ACTION>
        <MSGSTAT>SUCCESS</MSGSTAT>
      </FCUBS_HEADER>
      <FCUBS_BODY>
        <Main-Full>
          <MOBILE_NUMBER>09064703871</MOBILE_NUMBER>
        </Main-Full>
        <FCUBS_WARNING_RESP>
          <WARNING>
            <WCODE>ST-SAVE-023</WCODE>
            <WDESC>Record Successfully Retrieved</WDESC>
          </WARNING>
        </FCUBS_WARNING_RESP>
      </FCUBS_BODY>
    </QUERYQUERYCUSTCUSTOM_IOFS_RES>
  </S:Body>
</S:Envelope>

<?xml version="1.0" encoding="UTF-8"?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
  <S:Body>
    <QUERYQUERYCUSTCUSTOM_IOFS_RES xmlns="http://fcubs.ofss.com/service/FCUBSCustServiceCustom">
      <FCUBS_HEADER>
        <SOURCE>LAPOUSSD</SOURCE>
        <UBSCOMP>FCUBS</UBSCOMP>
        <MSGID>6123041222851438</MSGID>
        <CORRELID>null</CORRELID>
        <USERID>LAPOUSSD</USERID>
        <ENTITY>null</ENTITY>
        <BRANCH>900</BRANCH>
        <MODULEID>ST</MODULEID>
        <SERVICE>FCUBSCustServiceCustom</SERVICE>
        <OPERATION>QueryQueryCustCustom</OPERATION>
        <DESTINATION>LAPOUSSD</DESTINATION>
        <FUNCTIONID>STDCUSQY</FUNCTIONID>
        <ACTION>EXECUTEQUERY</ACTION>
        <MSGSTAT>SUCCESS</MSGSTAT>
      </FCUBS_HEADER>
      <FCUBS_BODY>
        <Main-Full>
          <MOBILE_NUMBER>09064703871</MOBILE_NUMBER>
        </Main-Full>
        <FCUBS_WARNING_RESP>
          <WARNING>
            <WCODE>ST-SAVE-023</WCODE>
            <WDESC>Record Successfully Retrieved</WDESC>
          </WARNING>
        </FCUBS_WARNING_RESP>
      </FCUBS_BODY>
    </QUERYQUERYCUSTCUSTOM_IOFS_RES>
  </S:Body>
</S:Envelope>
