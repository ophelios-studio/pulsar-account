-- #####################################################################################################################
-- GET USER ID
--
-- This function is often used as a "default" value for "created_by" or "updated_by" table columns. Tries to read a
-- postgres session variable loaded with @SET directive (or addSessionVariable from within Broker classes). If none
-- has been defined, returns NULL. If used as a default column value, its the developer responsibility to make sure
-- the field can be nullable or not depending on the situation.
-- #####################################################################################################################
CREATE FUNCTION pulsar.get_user_id() RETURNS INT
    LANGUAGE PLPGSQL AS $$
DECLARE
    user_id INT;
BEGIN
    BEGIN
        user_id := current_setting('zephyrus.user_id')::integer;
    EXCEPTION
        WHEN OTHERS THEN user_id := null;
    END;
    RETURN user_id;
END;
$$;

CREATE OR REPLACE FUNCTION pulsar.is_online(_last_activity TIMESTAMP)
    RETURNS BOOLEAN AS $$
BEGIN
    IF _last_activity IS NULL THEN
        RETURN FALSE;
    END IF;
    RETURN (
        (EXTRACT(MINUTE FROM AGE(NOW(), _last_activity)) <= 5) AND
        (EXTRACT(DAY FROM _last_activity) = DATE_PART('day', CURRENT_TIMESTAMP)) AND
        (EXTRACT(MONTH FROM _last_activity) = DATE_PART('month', CURRENT_TIMESTAMP)) AND
        (EXTRACT(YEAR FROM _last_activity) = DATE_PART('year', CURRENT_TIMESTAMP))
        );
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION pulsar.get_user_profile(user_id INT)
    RETURNS JSONB AS $$
DECLARE
    -- Declare a JSONB variable to store the user profile information
    user_profile JSONB;
BEGIN
    -- Query to fetch user profile information using the provided user_id
    SELECT ROW_TO_JSON(user_info)::JSONB
    INTO user_profile
    FROM (SELECT * FROM pulsar.view_user_profile WHERE id = user_id) AS user_info;

    -- Return the JSONB user profile information
    RETURN user_profile;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION pulsar.get_user_authentication(_user_id INT)
    RETURNS JSONB AS $$
DECLARE
    user_auth JSONB;
BEGIN
    SELECT ROW_TO_JSON(user_info)::JSONB
    INTO user_auth
    FROM (SELECT * FROM pulsar.view_user_authentication WHERE id = _user_id) AS user_info;
    RETURN user_auth;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION pulsar.get_user_oauth(_user_id INT)
    RETURNS JSONB AS $$
DECLARE
    user_auth JSONB;
BEGIN
    SELECT ROW_TO_JSON(user_info)::JSONB
    INTO user_auth
    FROM (SELECT * FROM pulsar.user_oauth WHERE id = _user_id) AS user_info;
    RETURN user_auth;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION pulsar.get_user_setting(_user_id INT)
    RETURNS JSONB AS $$
DECLARE
    user_auth JSONB;
BEGIN
    SELECT ROW_TO_JSON(user_info)::JSONB
    INTO user_auth
    FROM (SELECT * FROM pulsar.user_setting WHERE id = _user_id) AS user_info;
    RETURN user_auth;
END;
$$ LANGUAGE plpgsql;