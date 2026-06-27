-- Dummy Data for Withdrawal WhatsApp Testing
-- Run: C:\xampp\mysql\bin\mysql.exe -u root sewlovely < insert_dummy.sql

-- 1. Insert Dummy Users
INSERT INTO users (email, password_hash, role) VALUES 
('mitra_rina@test.com', '$2y$10$T7yWIimsYtaCPQyJIAp7PuzXbJnQVZ4D7Vq7.4o4OxIYJJRKqWiXi', 'mitra'),
('mitra_budi@test.com', '$2y$10$T7yWIimsYtaCPQyJIAp7PuzXbJnQVZ4D7Vq7.4o4OxIYJJRKqWiXi', 'mitra'),
('mitra_sari@test.com', '$2y$10$T7yWIimsYtaCPQyJIAp7PuzXbJnQVZ4D7Vq7.4o4OxIYJJRKqWiXi', 'mitra');

-- Get user IDs
SET @uid1 = (SELECT id FROM users WHERE email='mitra_rina@test.com');
SET @uid2 = (SELECT id FROM users WHERE email='mitra_budi@test.com');
SET @uid3 = (SELECT id FROM users WHERE email='mitra_sari@test.com');

-- 2. Insert Dummy Partners (dengan nomor WA)
INSERT INTO partners (user_id, full_name, whatsapp_number, birth_date, address, bank_name, account_number, account_holder, affiliate_code, status, is_active) VALUES
(@uid1, 'Rina Wulandari', '081234567890', '1995-03-15', 'Jl. Melati No. 12, Bandung', 'BCA', '1234567890', 'Rina Wulandari', 'AFF-RINA001', 'approved', 1),
(@uid2, 'Budi Santoso', '082198765432', '1990-07-22', 'Jl. Kenanga No. 5, Jakarta', 'BRI', '0987654321', 'Budi Santoso', 'AFF-BUDI002', 'approved', 1),
(@uid3, 'Sari Dewi', '085312348765', '1998-11-08', 'Jl. Anggrek No. 8, Surabaya', 'GoPay', '085312348765', 'Sari Dewi', 'AFF-SARI003', 'approved', 1);

-- Get partner IDs
SET @pid1 = (SELECT id FROM partners WHERE affiliate_code='AFF-RINA001');
SET @pid2 = (SELECT id FROM partners WHERE affiliate_code='AFF-BUDI002');
SET @pid3 = (SELECT id FROM partners WHERE affiliate_code='AFF-SARI003');

-- 3. Insert Commission (saldo awal)
INSERT INTO transactions (partner_id, type, amount, description, status) VALUES
(@pid1, 'commission', 150000, 'Komisi order INV-001', 'success'),
(@pid2, 'commission', 200000, 'Komisi order INV-002', 'success'),
(@pid3, 'commission', 175000, 'Komisi order INV-003', 'success');

-- 4. Insert Withdrawals (3 pending, 1 success, 1 rejected)
INSERT INTO transactions (partner_id, type, amount, description, status) VALUES
(@pid1, 'withdraw', 75000, 'Penarikan komisi - Rina', 'pending'),
(@pid2, 'withdraw', 120000, 'Penarikan komisi - Budi', 'pending'),
(@pid3, 'withdraw', 85500, 'Penarikan komisi - Sari', 'pending'),
(@pid1, 'withdraw', 50000, 'Penarikan sebelumnya - Rina', 'success'),
(@pid2, 'withdraw', 30000, 'Penarikan ditolak - Budi', 'rejected');
