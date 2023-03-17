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
                END IF;
                END IF;    
               helpers.json_response(error_msg.message(-1)); 
        END IF;    
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

-- mini statement

DECLARE
    p_customer          fcubs_customer%rowtype;
    f_account           fcubs_account%rowtype;
    p_account_created   BOOLEAN := false;
    jo                  json_object_t;
    f_account_id        NUMBER;
    t_details           json_array_t;
    p_charge_result varchar2(100);
    p_charge_tranid number;
BEGIN
    IF ( :phone IS NULL ) THEN
        helpers.json_response(error_msg.message(402));
        return;
    END IF;
    IF ( :pin IS NULL ) THEN
        helpers.json_response(error_msg.message(404));
        return;
    END IF;
    IF ( :account_number IS NULL ) THEN
        helpers.json_response(error_msg.message(601));
        return;
    END IF;
    -- p_customer := fcubs.get_customer_with_phone(p_phone=>:phone);
    IF ( fcubs.customer_exists(:phone) = false ) THEN
        helpers.json_response(error_msg.message(413));
        return;
    END IF;

    p_customer := fcubs.get_customer_with_phone(p_phone => :phone);

    -- check if user has access to check statement
    if p_customer.enable_mini_statement = 'NO' THEN
         helpers.json_response(error_msg.message(6000, 'Mini Statement Service not enabled'));
        return;
    end if;
    
    IF ( fcubs.is_authenticated(p_customer => p_customer, p_pin => :pin) ) THEN
        --- apply service charge, uncomment in production
        
        
        -- begin service charge
        fcubs.create_charge(
        p_phone  => :phone,
        p_debit_account => trim(:account_number), 
        p_result => p_charge_result, 
        p_narration => 'MINI_STATEMENT',
        p_local_currency_amount => 10,
        p_transaction_id => p_charge_tranid);
        if (upper(p_charge_result) != 'SUCCESS') then
            helpers.json_response(error_msg.message(1500,'Unable to apply service charge'));
            return;
        end if;
        -- end service charge
        
        -- get_account details
        t_details := fcubs.recent_transactions(p_account_number => :account_number);
        IF t_details IS NOT NULL THEN
            --htp.p(t_details.to_string);
            --return;
            jo := json_object_t.parse(error_msg.success_message);
            jo.put('items', t_details);
            -- always charge mini statements
            fcubs.log_activity(p_accno => :account_number, p_activity => 'statement', p_msisdn => :phone, p_custno => p_customer.custno
            );
            -- fcubs.successful_transactions(p_customer_id => p_customer.id, p_customer_no => p_customer.custno,
            -- p_amount => to_number(:amount), p_activity => 'statement', p_mobnum => :phone);
            
            helpers.json_response(jo.to_string);
            return;
        ELSE
            helpers.json_response(error_msg.message(1000));
            return;
        END IF;
    END IF;
    helpers.json_response(error_msg.message(409));
    return;
EXCEPTION
    WHEN OTHERS THEN
        error_msg.log_error;
        helpers.json_response(error_msg.message(-1,fcubs.no_response_message));
END;

-- schedule task
CREATE OR REPLACE PROCEDURE say_hello
IS
BEGIN
  DBMS_OUTPUT.PUT_LINE('Hello, World!');
END say_hello;
/

CREATE OR REPLACE PROGRAM say_hello_program
   AS
   BEGIN
     say_hello;
   END;


BEGIN
  DBMS_SCHEDULER.CREATE_SCHEDULE(
    schedule_name   => 'daily_schedule',
    start_date      => SYSTIMESTAMP,
    repeat_interval => 'FREQ=MINUTELY;INTERVAL=1');
END;
/
BEGIN
  DBMS_SCHEDULER.CREATE_JOB(
    job_name        => 'hello_world_job',
    program_name    => 'say_hello_program',
    start_date      => SYSTIMESTAMP,
    repeat_interval => 'daily_schedule');
END;
/
BEGIN
  DBMS_SCHEDULER.ENABLE('hello_world_job');
END;
