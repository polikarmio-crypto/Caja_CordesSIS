import re

with open("caja_cordes.sql", "r", encoding="utf-8") as f:
    content = f.read()

# Naive backtick to double quotes for identifiers
# We must be careful not to convert backticks inside strings, but usually SQL dumps use ' for strings.
content = content.replace('`', '"')

# Data types
content = re.sub(r'(?i)\bint\(\d+\)(?:\s+unsigned)?\b', 'INTEGER', content)
content = re.sub(r'(?i)\btinyint\(\d+\)(?:\s+unsigned)?\b', 'SMALLINT', content)
content = re.sub(r'(?i)\bdatetime\b', 'TIMESTAMP', content)
content = re.sub(r'(?i)\bdouble\b', 'DOUBLE PRECISION', content)

# AUTO_INCREMENT to SERIAL
content = re.sub(r'(?i)\bINTEGER\s+NOT\s+NULL\s+AUTO_INCREMENT\b', 'SERIAL', content)

# Remove table options
content = re.sub(r'(?i)ENGINE=InnoDB.*?;', ';', content)

# Remove PARTITION BY block completely (simplistic regex)
content = re.sub(r'(?i)PARTITION BY RANGE[\s\S]*?\);', ');', content)

with open("caja_cordes_pg.sql", "w", encoding="utf-8") as f:
    f.write(content)

print("SQL conversion done.")
