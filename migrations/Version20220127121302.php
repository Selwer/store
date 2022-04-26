<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220127121302 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cart (id SERIAL NOT NULL, user_id INT DEFAULT NULL, session_id VARCHAR(32) NOT NULL, product_id BIGINT NOT NULL, quantity INT NOT NULL, date_add TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, pod_zapros BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE form (id SERIAL NOT NULL, type VARCHAR(255) NOT NULL, fields JSON NOT NULL, send_email BOOLEAN NOT NULL, date_add TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, date_send TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "order" (id SERIAL NOT NULL, date_add TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, date_modif TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id INT NOT NULL, status VARCHAR(255) NOT NULL, total NUMERIC(10, 2) NOT NULL, comment VARCHAR(255) DEFAULT NULL, shipping_type VARCHAR(255) NOT NULL, shipping_address VARCHAR(255) DEFAULT NULL, payment_type VARCHAR(255) NOT NULL, shipping_price NUMERIC(10, 2) DEFAULT NULL, weight NUMERIC(15, 8) DEFAULT NULL, products JSON NOT NULL, guid VARCHAR(255) DEFAULT NULL, attach VARCHAR(255) DEFAULT NULL, pod_zapros BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE product (id SERIAL NOT NULL, guid VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, sku VARCHAR(255) DEFAULT NULL, brand VARCHAR(255) DEFAULT NULL, quantity INT NOT NULL, price NUMERIC(10, 2) NOT NULL, price0 NUMERIC(10, 2) NOT NULL, price1 NUMERIC(10, 2) NOT NULL, price2 NUMERIC(10, 2) NOT NULL, storage VARCHAR(255) NOT NULL, property JSON DEFAULT NULL, weight NUMERIC(15, 8) NOT NULL, volume NUMERIC(15, 8) DEFAULT NULL, image JSON DEFAULT NULL, analog JSON DEFAULT NULL, date_add TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, date_modif TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, import BOOLEAN NOT NULL, active BOOLEAN NOT NULL, date_import TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE product_prop (id SERIAL NOT NULL, category VARCHAR(255) DEFAULT NULL, weight VARCHAR(255) DEFAULT NULL, volume VARCHAR(255) DEFAULT NULL, tire_size VARCHAR(255) DEFAULT NULL, tire_type VARCHAR(255) DEFAULT NULL, tire_diameter VARCHAR(255) DEFAULT NULL, tire_model VARCHAR(255) DEFAULT NULL, tire_layer VARCHAR(255) DEFAULT NULL, tire_execut VARCHAR(255) DEFAULT NULL, tire_rim VARCHAR(255) DEFAULT NULL, fork_length VARCHAR(255) DEFAULT NULL, fork_section VARCHAR(255) DEFAULT NULL, fork_class VARCHAR(255) DEFAULT NULL, fork_load VARCHAR(255) DEFAULT NULL, acb_size VARCHAR(255) DEFAULT NULL, acb_tech VARCHAR(255) DEFAULT NULL, acb_type VARCHAR(255) DEFAULT NULL, acb_seria VARCHAR(255) DEFAULT NULL, acb_model VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE reset_password_request (id SERIAL NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7CE748AA76ED395 ON reset_password_request (user_id)');
        $this->addSql('COMMENT ON COLUMN reset_password_request.requested_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN reset_password_request.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, patronymic_name VARCHAR(255) DEFAULT NULL, mobile_phone VARCHAR(11) NOT NULL, inn VARCHAR(12) DEFAULT NULL, kpp VARCHAR(9) DEFAULT NULL, type_user VARCHAR(3) NOT NULL, guid VARCHAR(20) NOT NULL, certified BOOLEAN NOT NULL, blocked BOOLEAN NOT NULL, checked BOOLEAN NOT NULL, price_level VARCHAR(7) DEFAULT NULL, discount NUMERIC(4, 2) DEFAULT NULL, message VARCHAR(255) DEFAULT NULL, opfname VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE reset_password_request DROP CONSTRAINT FK_7CE748AA76ED395');
        $this->addSql('DROP TABLE cart');
        $this->addSql('DROP TABLE form');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_prop');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE "user"');
    }
}
