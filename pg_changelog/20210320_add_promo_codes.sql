
CREATE TABLE promo_codes (id UUID NOT NULL, offer_id UUID DEFAULT NULL, promo_code VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id));
ALTER TABLE actiondata.offer_execution ALTER offer_id DROP NOT NULL;
ALTER TABLE actiondata.offer_execution ALTER offer_link_id DROP NOT NULL;
