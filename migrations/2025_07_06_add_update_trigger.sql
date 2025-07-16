-- Trigger to generate accounts receivable when a sale is concluded after update
DROP TRIGGER IF EXISTS trg_gerar_contas_receber_upd;
DELIMITER $$
CREATE TRIGGER trg_gerar_contas_receber_upd
AFTER UPDATE ON VENDAS
FOR EACH ROW
BEGIN
    IF NEW.STATUS = 'CONCLUÍDA' AND OLD.STATUS <> 'CONCLUÍDA' THEN
        DECLARE i INT DEFAULT 1;
        DECLARE vencimento DATE;
        DECLARE valor_parcela DECIMAL(10,2);
        SET valor_parcela = NEW.VALOR_TOTAL / COALESCE(NEW.NUMERO_PARCELAS,1);
        SET vencimento = COALESCE(NEW.DATA_VENCIMENTO_PARCELA, CURDATE());
        WHILE i <= COALESCE(NEW.NUMERO_PARCELAS,1) DO
            INSERT INTO CONTAS_A_RECEBER (
                ID_VENDA, ID_CLIENTE, VALOR, DATA_VENCIMENTO, STATUS, ID_EMPRESA
            ) VALUES (
                NEW.ID, NEW.ID_CLIENTE, valor_parcela,
                DATE_ADD(vencimento, INTERVAL (i - 1) MONTH),
                'PENDENTE', NEW.ID_EMPRESA
            );
            SET i = i + 1;
        END WHILE;
    END IF;
END$$
DELIMITER ;
