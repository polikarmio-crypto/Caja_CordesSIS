import pandas as pd
import json

excel_file = "/home/polikarmio/Caja_Cordes_SIS/caja_cordes/BACKLOGS_Y_GRAFICA_ACTUALIZADO.xlsx"

xls = pd.ExcelFile(excel_file)
print(f"Sheet names: {xls.sheet_names}")

for sheet_name in xls.sheet_names:
    print(f"\n--- Sheet: {sheet_name} ---")
    df = pd.read_excel(xls, sheet_name=sheet_name)
    print(df.head(10).to_string())

