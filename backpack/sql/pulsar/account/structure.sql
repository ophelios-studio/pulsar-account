-- ##################################################################################################################
-- USER & AUTHENTICATION
-- ##################################################################################################################
CREATE TABLE pulsar.user_profile
(
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY, -- Prevent id override
    firstname TEXT NOT NULL,
    lastname TEXT NOT NULL,
    email TEXT NOT NULL,
    avatar TEXT NULL DEFAULT NULL,
    last_activity TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT now()
);

CREATE TABLE pulsar.user_authentication
(
    id INT PRIMARY KEY NOT NULL, -- 1 to 1 relationship with user_profile
    username TEXT NOT NULL, -- Used to authenticate (could be a copy of the email)
    password_hash TEXT NULL DEFAULT NULL,
    password_compromised BOOLEAN NOT NULL DEFAULT FALSE,
    password_reset BOOLEAN NOT NULL DEFAULT FALSE,
    activation TEXT NULL, -- Random token used for account activation
    validator TEXT NOT NULL, -- Special random value to use as specific salting for remember me
    grace_secret TEXT DEFAULT NULL, -- When a user chose to skip MFA for 20 days
    locked BOOLEAN DEFAULT FALSE,
    last_connection TIMESTAMP DEFAULT NULL, -- Last time the user authenticated on the application
    superuser BOOLEAN DEFAULT FALSE, -- Gives access to everything
    FOREIGN KEY (id) REFERENCES pulsar.user_profile (id)
        ON DELETE CASCADE -- Delete authentication upon user deletion
);

CREATE TABLE pulsar.user_oauth
(
    id INT PRIMARY KEY NOT NULL, -- 1 to 1 relationship with user_profile
    provider TEXT NOT NULL, -- OAuth provider name (e.g., github, facebook, google)
    provider_user_id TEXT NOT NULL, -- Unique user ID returned by the OAuth provider
    access_token TEXT NOT NULL,
    connected_at TIMESTAMP DEFAULT now(),
    FOREIGN KEY (id) REFERENCES pulsar.user_profile (id)
        ON DELETE CASCADE -- Delete authentication upon user deletion
);

CREATE TABLE pulsar.user_setting
(
    id INT PRIMARY KEY NOT NULL, -- 1 to 1 relationship with user_profile
    skip_mfa_warning BOOLEAN DEFAULT FALSE, -- Display warning about MFA activation
    preferred_locale TEXT DEFAULT 'fr_CA', -- Default application language
    last_release_seen TEXT NULL DEFAULT NULL, -- Identifies which release the user last seen (for update)
    FOREIGN KEY (id) REFERENCES pulsar.user_profile (id)
        ON DELETE CASCADE -- Delete settings upon user deletion
);

-- ##################################################################################################################
-- REMEMBER ME
-- ##################################################################################################################
CREATE TABLE pulsar.user_remember_token
(
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY, -- Prevent id override
    identifier TEXT, -- Remember me identity
    validation TEXT, -- Validation hash to authenticate identity
    iteration TEXT, -- Updates each automated login (sequencer)
    ip_address TEXT NULL DEFAULT NULL,
    user_agent JSONB NULL DEFAULT NULL,
    expire TIMESTAMP,
    access TIMESTAMP DEFAULT now(), -- Last time the token was used for automated login
    created_at TIMESTAMP DEFAULT now(),
    user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES pulsar.user_profile (id)
        ON DELETE CASCADE -- If user is deleted, delete his tokens
);

-- ##################################################################################################################
-- MFA METHODS & RECOVERY
-- ##################################################################################################################
CREATE TABLE pulsar.user_mfa
(
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY, -- Prevent id override
    type TEXT NOT NULL, -- Email, sms, otp, yubi, ...
    secret TEXT NULL DEFAULT NULL, -- Optional associated secret (OTP)
    created_at TIMESTAMP DEFAULT now(),
    user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES pulsar.user_profile (id)
        ON DELETE CASCADE
);

CREATE TABLE pulsar.user_recovery_code
(
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY, -- Prevent id override
    code TEXT NOT NULL UNIQUE,
    used_date TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES pulsar.user_profile (id)
        ON DELETE CASCADE
);
