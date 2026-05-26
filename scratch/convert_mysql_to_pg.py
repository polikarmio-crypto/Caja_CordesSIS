import re
import os

def convert_mysql_to_pg(mysql_file_path, pg_file_path):
    print(f"Converting {mysql_file_path} to {pg_file_path}...")
    
    with open(mysql_file_path, "r", encoding="utf-8") as f:
        content = f.read()

    # Pre-parse ALTER TABLE statements to find AUTO_INCREMENT values and PRIMARY KEYS
    # We will use this to define SERIAL PRIMARY KEY directly in CREATE TABLE.
    # In mysql dump:
    # ALTER TABLE `tablename` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5012;
    auto_increments = {}
    auto_inc_matches = re.finditer(r'(?i)ALTER\s+TABLE\s+`([^`]+)`\s+MODIFY\s+`([^`]+)`\s+[^;]+AUTO_INCREMENT=(\d+)', content)
    for m in auto_inc_matches:
        table_name, col_name, start_val = m.groups()
        auto_increments[table_name] = (col_name, int(start_val))
        print(f"Found AUTO_INCREMENT for table {table_name}: col={col_name}, start={start_val}")

    # Find primary keys defined in ALTER TABLE
    # ALTER TABLE `tablename` ADD PRIMARY KEY (`col1`, `col2`);
    primary_keys = {}
    pk_matches = re.finditer(r'(?i)ALTER\s+TABLE\s+`([^`]+)`\s+ADD\s+PRIMARY\s+KEY\s+\(([^)]+)\);', content)
    for m in pk_matches:
        table_name, cols = m.groups()
        cols_list = [c.strip().replace('`', '"') for m_col in re.finditer(r'`([^`]+)`', cols) for c in [m_col.group(1)]]
        primary_keys[table_name] = cols_list
        print(f"Found PRIMARY KEY for table {table_name}: {cols_list}")

    # Now let's split the file into statements or parse block by block
    # A standard mysqldump statement is either CREATE TABLE, INSERT, ALTER TABLE, trigger, etc.
    # Let's remove comments we don't need, or DELIMITER statements.
    
    # We will replace backticks with double quotes for all identifiers outside of strings.
    # But wait, to be safe, let's parse blocks.
    
    lines = content.split('\n')
    output = []
    
    # Add PostgreSQL defaults
    output.append("-- PostgreSQL Migrated Dump")
    output.append("SET statement_timeout = 0;")
    output.append("SET lock_timeout = 0;")
    output.append("SET client_encoding = 'UTF8';")
    output.append("SET standard_conforming_strings = on;")
    output.append("SET check_function_bodies = false;")
    output.append("SET xmloption = content;")
    output.append("SET client_min_messages = warning;")
    output.append("SET row_security = off;")
    output.append("")
    
    # Translate stored procedures and functions:
    # sp_cancelar_citas_vencidas
    output.append("""
CREATE OR REPLACE PROCEDURE sp_cancelar_citas_vencidas() LANGUAGE plpgsql AS $$
BEGIN
    UPDATE citas 
    SET estado = 'cancelada' 
    WHERE estado = 'pendiente' AND fecha_hora < NOW();
END;
$$;
""")

    output.append("""
CREATE OR REPLACE FUNCTION fn_total_citas_paciente(p_paciente_id INTEGER) RETURNS INTEGER LANGUAGE plpgsql AS $$
DECLARE
    v_total INTEGER;
BEGIN
    SELECT COUNT(*) INTO v_total FROM citas WHERE paciente_id = p_paciente_id;
    RETURN v_total;
END;
$$;
""")

    # We will skip DELIMITER blocks and procedured/function definitions in the original file
    in_delimiter = False
    in_create_table = False
    current_table = None
    table_lines = []
    
    for i, line in enumerate(lines):
        line_strip = line.strip()
        
        # Skip original procedure / function / delimiter lines
        if line_strip.upper().startswith("DELIMITER"):
            continue
        if line_strip.upper().startswith("CREATE DEFINER") and ("PROCEDURE" in line_strip.upper() or "FUNCTION" in line_strip.upper()):
            in_delimiter = True
            continue
        if in_delimiter:
            if "END$$" in line_strip or "END $$" in line_strip or "$$" in line_strip:
                in_delimiter = False
            continue
            
        # Ignore comments or XAMPP specific header commands
        if line_strip.startswith("/*!") or line_strip.startswith("SET ") or line_strip.startswith("START TRANSACTION") or line_strip.startswith("COMMIT"):
            if "INSERT INTO" not in line:
                continue
                
        # Parse CREATE TABLE
        # CREATE TABLE `tablename` (
        create_table_match = re.match(r'(?i)^CREATE\s+TABLE\s+`([^`]+)`\s*\(', line_strip)
        if create_table_match:
            in_create_table = True
            current_table = create_table_match.group(1)
            table_lines = []
            continue
            
        if in_create_table:
            if line_strip.startswith(")") or line_strip.startswith(" ENGINE="):
                in_create_table = False
                # We have the table definition, let's convert and output it
                pg_table_ddl = convert_table_ddl(current_table, table_lines, auto_increments, primary_keys)
                output.append(pg_table_ddl)
                output.append("")
                current_table = None
                table_lines = []
            else:
                table_lines.append(line)
            continue
            
        # Process regular lines
        # Replace backticks with double quotes for INSERT INTO statements
        if line_strip.upper().startswith("INSERT INTO"):
            # Replace backticks in table name and columns
            line_conv = replace_backticks(line)
            output.append(line_conv)
            continue
            
        # Parse ALTER TABLE
        # ALTER TABLE `tablename`
        alter_table_match = re.match(r'(?i)^ALTER\s+TABLE\s+`([^`]+)`', line_strip)
        if alter_table_match:
            # We want to keep ALTER TABLE for ADD CONSTRAINT (foreign keys),
            # but ignore ADD PRIMARY KEY, ADD KEY, ADD UNIQUE KEY, MODIFY
            # Let's inspect the next lines or see if it's a single line or multi-line
            # In mysql dumps, alter table statements are usually multi-line
            # Let's read the lines until a semicolon
            j = i
            alter_lines = []
            while j < len(lines):
                alter_lines.append(lines[j])
                if lines[j].strip().endswith(";"):
                    break
                j += 1
            
            alter_block = "\n".join(alter_lines)
            process_alter_block(alter_block, output)
            # Skip lines in the outer loop
            # But wait, we cannot easily modify outer loop index in python 'for' loop directly, 
            # so we'll just track skipped index
            continue
            
    # Write output to pg_file_path
    # Wait, we need to handle skipped indexes in the loop. Let's rewrite the loop using while to be absolutely safe!
    
def replace_backticks(text):
    # Safe replacement of backticks outside strings
    # Mysql dump usually uses ' for strings, so ` is only for identifiers
    return text.replace('`', '"')

def convert_table_ddl(table_name, table_lines, auto_increments, primary_keys):
    if table_name == "usuarios":
        table_lines = list(table_lines)
        last_idx = -1
        for i in range(len(table_lines) - 1, -1, -1):
            if table_lines[i].strip():
                last_idx = i
                break
        if last_idx != -1:
            if not table_lines[last_idx].strip().endswith(","):
                table_lines[last_idx] = table_lines[last_idx].rstrip() + ","
        table_lines.append('  `intentos_fallidos` int(11) NOT NULL DEFAULT 0,')
        table_lines.append('  `bloqueado_hasta` datetime DEFAULT NULL,')
        table_lines.append('  `reset_token` varchar(100) DEFAULT NULL,')
        table_lines.append('  `reset_token_expira` datetime DEFAULT NULL,')
        table_lines.append('  `two_factor_code` varchar(10) DEFAULT NULL,')
        table_lines.append('  `two_factor_expira` datetime DEFAULT NULL')
        
    pg_lines = []
    has_serial_id = table_name in auto_increments
    serial_col = auto_increments[table_name][0] if has_serial_id else None
    
    for line in table_lines:
        line_strip = line.strip()
        if not line_strip:
            continue
        
        # Replace backticks
        line_conv = replace_backticks(line)
        line_conv_strip = line_conv.strip()
        
        # Find column definition
        # "colname" type ...
        col_match = re.match(r'^"([^"]+)"\s+(.+)$', line_conv_strip)
        if col_match:
            col_name, col_def = col_match.groups()
            
            # If this is the serial autoincrement column
            if has_serial_id and col_name == serial_col:
                # Replace with SERIAL PRIMARY KEY
                # e.g., "id" SERIAL PRIMARY KEY
                # Let's strip trailing commas
                trailing_comma = "," if line_conv_strip.endswith(",") else ""
                pg_lines.append(f'  "{col_name}" SERIAL PRIMARY KEY{trailing_comma}')
                continue
                
            # If it's a column definition, convert types
            col_def = re.sub(r'(?i)\bint\(\d+\)(?:\s+unsigned)?', 'INTEGER', col_def)
            col_def = re.sub(r'(?i)\btinyint\(\d+\)(?:\s+unsigned)?', 'SMALLINT', col_def)
            col_def = re.sub(r'(?i)\bdatetime\b', 'TIMESTAMP', col_def)
            col_def = re.sub(r'(?i)\bdouble\b', 'DOUBLE PRECISION', col_def)
            col_def = re.sub(r'(?i)\bvarchar\(\d+\)\s+CHARACTER\s+SET\s+\w+\s+COLLATE\s+[\w_]+', lambda m: m.group().split('CHARACTER')[0].strip(), col_def)
            col_def = re.sub(r'(?i)\bunsigned\b', '', col_def)
            
            pg_lines.append(f'  "{col_name}" {col_def}')
        else:
            # If it is not a column definition (e.g. key constraint inside CREATE TABLE, which we'll handle at the bottom)
            if "PRIMARY KEY" in line_conv_strip:
                # Pivot tables composite primary key
                pg_lines.append(line_conv)
            elif "KEY" in line_conv_strip or "UNIQUE KEY" in line_conv_strip:
                # Skip inline keys, we'll create them using CREATE INDEX at the end
                continue
            else:
                pg_lines.append(line_conv)
                
    # Clean up trailing commas in pg_lines
    cleaned_lines = []
    for i, l in enumerate(pg_lines):
        l_strip = l.strip()
        if i == len(pg_lines) - 1:
            # Last line shouldn't have trailing comma if it's the end of definition
            if l_strip.endswith(","):
                cleaned_lines.append(l.rstrip().rstrip(","))
            else:
                cleaned_lines.append(l)
        else:
            cleaned_lines.append(l)
            
    body = "\n".join(cleaned_lines)
    return f'CREATE TABLE "{table_name}" (\n{body}\n);'

def process_alter_block(alter_block, output):
    # Parse ALTER TABLE `tablename` ADD ...
    # Replace backticks with double quotes
    alter_block = replace_backticks(alter_block)
    
    # We split the block by commas inside ADD statements
    # e.g.,
    # ALTER TABLE "usuarios"
    #   ADD PRIMARY KEY ("id"),
    #   ADD UNIQUE KEY "idx_email" ("email"),
    #   ADD KEY "rol_id" ("rol_id");
    
    lines = alter_block.split('\n')
    table_match = re.match(r'(?i)^ALTER\s+TABLE\s+"([^"]+)"', lines[0].strip())
    if not table_match:
        return
    table_name = table_match.group(1)
    
    # Let's rebuild and parse actions
    actions_str = " ".join(lines[1:])
    # Strip semicolon at end
    actions_str = actions_str.strip().rstrip(";")
    
    # Split actions by comma, being careful not to split inside parenthesis
    # Let's write a simple parenthesis-aware splitter
    actions = []
    current_action = []
    paren_depth = 0
    for char in actions_str:
        if char == '(':
            paren_depth += 1
        elif char == ')':
            paren_depth -= 1
        
        if char == ',' and paren_depth == 0:
            actions.append("".join(current_action).strip())
            current_action = []
        else:
            current_action.append(char)
    if current_action:
        actions.append("".join(current_action).strip())
        
    for action in actions:
        action_upper = action.upper()
        if action_upper.startswith("ADD CONSTRAINT"):
            # Keep foreign key constraints!
            output.append(f'ALTER TABLE "{table_name}" {action};')
        elif action_upper.startswith("ADD UNIQUE KEY") or action_upper.startswith("ADD UNIQUE INDEX"):
            # ALTER TABLE "usuarios" ADD UNIQUE KEY "idx_email" ("email")
            # Convert to CREATE UNIQUE INDEX
            m = re.match(r'(?i)^ADD\s+UNIQUE\s+(?:KEY|INDEX)\s+"([^"]+)"\s*\(([^)]+)\)', action)
            if m:
                idx_name, cols = m.groups()
                output.append(f'CREATE UNIQUE INDEX "{idx_name}" ON "{table_name}" ({cols});')
        elif action_upper.startswith("ADD KEY") or action_upper.startswith("ADD INDEX"):
            # ALTER TABLE "usuarios" ADD KEY "rol_id" ("rol_id")
            # Convert to CREATE INDEX
            # Note: in MySQL, key might not have a name or have double quotes, let's parse
            m = re.match(r'(?i)^ADD\s+(?:KEY|INDEX)\s+"([^"]+)"\s*\(([^)]+)\)', action)
            if m:
                idx_name, cols = m.groups()
                output.append(f'CREATE INDEX "{idx_name}" ON "{table_name}" ({cols});')
            else:
                m_noname = re.match(r'(?i)^ADD\s+(?:KEY|INDEX)\s*\(([^)]+)\)', action)
                if m_noname:
                    cols = m_noname.group(1)
                    idx_name = f"idx_{table_name}_{cols.replace(' ', '').replace('\"', '').replace(',', '_')}"
                    output.append(f'CREATE INDEX "{idx_name}" ON "{table_name}" ({cols});')

# Let's write the robust parser using while loop to skip lines
def run_full_migration(mysql_file_path, pg_file_path):
    print(f"Loading {mysql_file_path}...")
    with open(mysql_file_path, "r", encoding="utf-8") as f:
        content = f.read()

    # Pre-parse ALTER TABLE statements for sequences and primary keys
    auto_increments = {}
    auto_inc_matches = re.finditer(r'(?i)ALTER\s+TABLE\s+`([^`]+)`\s+MODIFY\s+`([^`]+)`\s+[^;]+AUTO_INCREMENT=(\d+)', content)
    for m in auto_inc_matches:
        table_name, col_name, start_val = m.groups()
        auto_increments[table_name] = (col_name, int(start_val))

    primary_keys = {}
    pk_matches = re.finditer(r'(?i)ALTER\s+TABLE\s+`([^`]+)`\s+ADD\s+PRIMARY\s+KEY\s+\(([^)]+)\);', content)
    for m in pk_matches:
        table_name, cols = m.groups()
        cols_list = [c.strip().replace('`', '') for m_col in re.finditer(r'`([^`]+)`', cols) for c in [m_col.group(1)]]
        primary_keys[table_name] = cols_list

    lines = content.split('\n')
    output = []
    
    # Add PostgreSQL defaults
    output.append("-- PostgreSQL Migrated Dump")
    output.append("SET statement_timeout = 0;")
    output.append("SET lock_timeout = 0;")
    output.append("SET client_encoding = 'UTF8';")
    output.append("SET standard_conforming_strings = on;")
    output.append("SET check_function_bodies = false;")
    output.append("SET xmloption = content;")
    output.append("SET client_min_messages = warning;")
    output.append("SET row_security = off;")
    output.append("")
    
    # Procedures and Functions
    output.append("""
CREATE OR REPLACE PROCEDURE sp_cancelar_citas_vencidas() LANGUAGE plpgsql AS $$
BEGIN
    UPDATE citas 
    SET estado = 'cancelada' 
    WHERE estado = 'pendiente' AND fecha_hora < NOW();
END;
$$;
""")

    output.append("""
CREATE OR REPLACE FUNCTION fn_total_citas_paciente(p_paciente_id INTEGER) RETURNS INTEGER LANGUAGE plpgsql AS $$
DECLARE
    v_total INTEGER;
BEGIN
    SELECT COUNT(*) INTO v_total FROM citas WHERE paciente_id = p_paciente_id;
    RETURN v_total;
END;
$$;
""")

    idx = 0
    in_delimiter = False
    in_partition = False
    
    while idx < len(lines):
        line = lines[idx]
        line_strip = line.strip()
        
        if not line_strip:
            idx += 1
            continue
            
        if line_strip.upper().startswith("PARTITION BY RANGE"):
            in_partition = True
            idx += 1
            continue
            
        if in_partition:
            if line_strip.endswith(");"):
                in_partition = False
            idx += 1
            continue
            
        # Skip comments
        if line_strip.startswith("--") or line_strip.startswith("/*"):
            # Check if this comment is the autoincrement section
            if "AUTO_INCREMENT" in line_strip:
                # We skip the entire AUTO_INCREMENT section
                idx += 1
                while idx < len(lines):
                    if lines[idx].strip().startswith("--") and "Restricciones" in lines[idx]:
                        break
                    idx += 1
                continue
            output.append(line)
            idx += 1
            continue
            
        # Skip XAMPP header lines or transaction controls
        if line_strip.startswith("SET ") or line_strip.startswith("START TRANSACTION") or line_strip.startswith("COMMIT"):
            idx += 1
            continue
            
        # DELIMITER handling
        if line_strip.upper().startswith("DELIMITER"):
            idx += 1
            continue
            
        if line_strip.upper().startswith("CREATE DEFINER") and ("PROCEDURE" in line_strip.upper() or "FUNCTION" in line_strip.upper()):
            in_delimiter = True
            idx += 1
            while idx < len(lines) and in_delimiter:
                if "END$$" in lines[idx] or "END $$" in lines[idx] or "$$" in lines[idx]:
                    in_delimiter = False
                idx += 1
            continue
            
        # CREATE TABLE
        create_table_match = re.match(r'(?i)^CREATE\s+TABLE\s+`([^`]+)`\s*\(', line_strip)
        if create_table_match:
            table_name = create_table_match.group(1)
            table_lines = []
            idx += 1
            while idx < len(lines):
                cur_line = lines[idx]
                cur_strip = cur_line.strip()
                if cur_strip.startswith(")") or cur_strip.startswith(" ENGINE="):
                    # End of CREATE TABLE
                    idx += 1
                    break
                table_lines.append(cur_line)
                idx += 1
                
            # Process table definition
            pg_table_ddl = convert_table_ddl(table_name, table_lines, auto_increments, primary_keys)
            output.append(pg_table_ddl)
            output.append("")
            continue
            
        # INSERT INTO
        if line_strip.upper().startswith("INSERT INTO"):
            # Mysql inserts might span multiple lines in standard dumps, but let's check
            output.append(replace_backticks(line))
            idx += 1
            continue
            
        # ALTER TABLE
        alter_table_match = re.match(r'(?i)^ALTER\s+TABLE\s+`([^`]+)`', line_strip)
        if alter_table_match:
            alter_lines = []
            while idx < len(lines):
                alter_lines.append(lines[idx])
                if lines[idx].strip().endswith(";"):
                    idx += 1
                    break
                idx += 1
            alter_block = "\n".join(alter_lines)
            process_alter_block(alter_block, output)
            continue
            
        # Regular fallback
        output.append(replace_backticks(line))
        idx += 1

    # Add the custom modules tables
    output.append("""
-- Nuevas tablas rescatadas del proyecto aleslisis --

CREATE TABLE "sucursales" (
    "id" SERIAL PRIMARY KEY,
    "nombre" VARCHAR(100),
    "ubicacion" VARCHAR(255),
    "horarios" VARCHAR(100),
    "limitesgeocerca" TEXT,
    "estado" VARCHAR(20) DEFAULT 'activo',
    "id_administrador" INTEGER
);

CREATE TABLE "categorias_insumo" (
    "id" SERIAL PRIMARY KEY,
    "nombre" VARCHAR(50),
    "descripcion" TEXT,
    "estado" VARCHAR(20) DEFAULT 'activo'
);

CREATE TABLE "insumos" (
    "id" SERIAL PRIMARY KEY,
    "nombre" VARCHAR(100),
    "descripcion" TEXT,
    "precio_unitario" NUMERIC(10,2),
    "cantidad" INTEGER,
    "estado" VARCHAR(20) DEFAULT 'activo',
    "id_categoria" INTEGER REFERENCES "categorias_insumo" ("id") ON DELETE SET NULL
);

-- SPRINT 10 --
CREATE TABLE "calificaciones" (
    "id" SERIAL PRIMARY KEY,
    "cita_id" INTEGER REFERENCES "citas" ("id") ON DELETE CASCADE,
    "paciente_id" INTEGER REFERENCES "pacientes" ("id") ON DELETE CASCADE,
    "medico_id" INTEGER REFERENCES "medicos" ("id") ON DELETE CASCADE,
    "puntuacion" INTEGER CHECK (puntuacion >= 1 AND puntuacion <= 5),
    "comentarios" TEXT,
    "fecha_calificacion" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- SPRINT 11 (Optimización) --
CREATE INDEX "idx_citas_fecha_hora" ON "citas" ("fecha_hora");
CREATE INDEX "idx_citas_estado" ON "citas" ("estado");
CREATE INDEX "idx_pacientes_dni" ON "pacientes" ("dni");
CREATE INDEX "idx_resultados_lab_paciente" ON "resultados_laboratorio" ("paciente_id");
""")

    # Add Trigger definition at the end
    output.append("""
-- Disparadores
CREATE OR REPLACE FUNCTION trg_auditoria_citas_insert_fn() RETURNS TRIGGER AS $$
DECLARE
    v_usuario_id INTEGER;
BEGIN
    SELECT usuario_id INTO v_usuario_id FROM pacientes WHERE id = NEW.paciente_id LIMIT 1;
    INSERT INTO auditoria (usuario_id, accion, tabla, fecha)
    VALUES (v_usuario_id, 'Agendó Cita ID: ' || NEW.id, 'citas', NOW());
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_auditoria_citas_insert
AFTER INSERT ON citas
FOR EACH ROW
EXECUTE FUNCTION trg_auditoria_citas_insert_fn();
""")

    # Set sequence values for all SERIAL tables so they restart correctly!
    output.append("\n-- Set sequence values to max ID for correct autoincrement --\n")
    for tbl, (col, _) in auto_increments.items():
        output.append(f"SELECT setval(pg_get_serial_sequence('\"{tbl}\"', '{col}'), COALESCE(MAX(\"{col}\"), 1)) FROM \"{tbl}\";")
        
    output.append("\nCOMMIT;\n")

    with open(pg_file_path, "w", encoding="utf-8") as f:
        f.write("\n".join(output))
    print("PostgreSQL migration completely successful!")

if __name__ == "__main__":
    run_full_migration("caja_cordes.sql", "caja_cordes_pg.sql")
