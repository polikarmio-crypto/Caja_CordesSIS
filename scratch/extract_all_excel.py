import pandas as pd
import json

excel_file = "/home/polikarmio/Caja_Cordes_SIS/caja_cordes/BACKLOGS_Y_GRAFICA_ACTUALIZADO.xlsx"
xls = pd.ExcelFile(excel_file)

with open("scratch/excel_full.txt", "w") as f:
    for sheet_name in xls.sheet_names:
        f.write(f"\n--- Sheet: {sheet_name} ---\n")
        df = pd.read_excel(xls, sheet_name=sheet_name)
        f.write(df.to_string())

