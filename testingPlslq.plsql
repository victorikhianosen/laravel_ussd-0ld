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
        IF ( p_bvn_data IS NULL ) THEN
            p_bvn_data := util_paystack.verify_bvn(:bvn);
            jo := json_object_t.parse(p_bvn_data);
            IF ( jo.get_string('PhoneNumber1')  IS NOT NULL ) THEN
                IF( jo.get_string('PhoneNumber1') = :phone) THEN
                    f_firstname := p_details.get_string('FirstName');
                    f_lastname := p_details.get_string('LastName');
                    f_dob := p_details.get_string('DateOfBirth');

                ELSE IF (jo.get_string('PhoneNumber1') <> :phone) THEN
                    helpers.json_response(error_msg.message(455, 'Could not resolve BVN with phone')); 
                    return; 
                  ELSE
                helpers.json_response(error_msg.message(501, 'Could not resolve BVN'));
                return;     
                END IF;
            END IF;    
            END IF;    
               helpers.json_response(error_msg.message(-1)); 
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


-- previous code
DECLARE
    p_bvn_data    VARCHAR2(4000);
    jo            json_object_t;
    p_details     json_object_t;
    p_bvn_phone   VARCHAR2(20) := NULL;
    f_phone       VARCHAR2(20);
    f_firstname   VARCHAR2(200);
    f_lastname    VARCHAR2(200);
    f_dob         VARCHAR2(20);
    f_message     VARCHAR2(200);
    p_string      VARCHAR2(4000);
    p_resolved    BOOLEAN := false;
    t_details     json_array_t;
BEGIN
    IF ( :phone IS NULL ) THEN
        helpers.json_response(error_msg.message(402));
        return;
    END IF;
    IF ( :bvn IS NULL ) THEN
        helpers.json_response(error_msg.message(501));
        return;
    END IF;
    -- p_bvn_data := util_paystack.get_resolved_bvn(:bvn);
    --     --htp.p(p_bvn_data);
    --     --return;
    IF ( p_bvn_data IS NULL ) THEN
            p_bvn_data := util_paystack.verify_bvn(:bvn);
            jo := json_object_t.parse(p_bvn_data);
            IF ( jo.get_string('PhoneNumber1')  IS NOT NULL ) THEN
                IF( jo.get_string('PhoneNumber1') = :phone) THEN
                    owa_util.mime_header ('application/json', true); 
                    htp.p(jo.stringify);
                    return ; 
                ELSE IF (jo.get_string('PhoneNumber1') <> :phone) THEN
                    helpers.json_response(error_msg.message(455, 'Could not resolve BVN with phone')); 
                    return;  
                END IF;
            END IF;    
               helpers.json_response(error_msg.message(-1)); 
               return;
    END IF;   
     helpers.json_response(error_msg.message(-1));  
     return;
  END IF; 
    --     --p_resolved := jo.get_boolean('status');
    --     -- IF ( p_resolved ) THEN
    --     IF instr(p_bvn_data, 'data') >= 1 THEN
    --         p_details := jo.get_object('data');
    --         p_bvn_phone := p_details.get_string('mobile');
    --     -- bvn must match the phone
    --         IF ( substr(p_bvn_phone, -10) != substr(:phone, -10) ) THEN
    --         -- bvn not matched with phon enumber
    --             helpers.json_response(error_msg.message(420));
    --             return;
    --         END IF;
    --     --  show bvn data 
    --         --- http://pcash.novajii.com/ords/
    --         f_firstname := p_details.get_string('first_name');
    --         f_lastname := p_details.get_string('last_name');
    --         f_dob := p_details.get_string('formatted_dob');
    --         jo := json_object_t.parse(error_msg.success_message);
    --          -- jo.put('customer_number', f_account.custno);
    --         jo.put('first_name', f_firstname);
    --         jo.put('last_name', f_lastname);
    --         jo.put('phone', p_bvn_phone);
    --         jo.put('bvn', :bvn);
    --         jo.put('dob', f_dob);
    --         helpers.json_response(jo.to_string);
    --         return;
    --     END IF;
    --     helpers.json_response(error_msg.message(501, 'Could not resolve BVN with phone'));
    --     return;
    -- END IF;
    -- helpers.json_response(error_msg.message(501, 'Could not resolve BVN'));
    -- return;
    EXCEPTION
        WHEN OTHERS THEN
            error_msg.log_error;
            helpers.json_response(error_msg.message(-1));
END;
