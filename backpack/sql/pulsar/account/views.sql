CREATE VIEW pulsar.view_user_profile AS
    SELECT
        pro.id,
        firstname,
        lastname,
        concat(firstname, ' ', lastname) as fullname,
        SUBSTRING(REGEXP_REPLACE(concat(firstname, ' ', lastname), '(\S)\S*\s*', '\1', 'g') FROM 1 FOR 2) as initials,
        username,
        email,
        avatar,
        pulsar.is_online(last_activity) as online,
        created_at
    FROM pulsar.user_profile pro
    JOIN pulsar.user_authentication
      ON pro.id = user_authentication.id;

CREATE VIEW pulsar.view_user_authentication AS
  SELECT
      id,
      username,
      password_hash,
      password_compromised,
      password_reset,
      activation,
      validator,
      locked,
      last_connection,
      superuser,
      grace_secret,
      login_provider,
      login_provider_user_id,
      login_provider_access_token,

      -- MFA
      (
          SELECT jsonb_agg(jsonb_build_object(
              'id', mfa.id,
              'type', mfa.type,
              'secret', mfa.secret,
              'created_at', mfa.created_at
          ))
          FROM pulsar.user_mfa mfa
          WHERE mfa.user_id = user_authentication.id
      ) AS mfa_methods

  FROM pulsar.user_authentication;

CREATE VIEW pulsar.view_user AS
SELECT

    -- Base columns
    vup.*,

    -- Authentication
    pulsar.get_user_authentication(id) as authentication,

    -- Settings
    pulsar.get_user_setting(id) as setting

FROM pulsar.view_user_profile vup;

