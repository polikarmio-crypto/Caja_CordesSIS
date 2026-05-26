import pandas as pd
import re

excel_file = "/home/polikarmio/Caja_Cordes_SIS/caja_cordes/BACKLOGS_Y_GRAFICA_ACTUALIZADO.xlsx"
out_file = "/home/polikarmio/Caja_Cordes_SIS/caja_cordes/BACKLOGS_Y_GRAFICA_ACTUALIZADO_V2.xlsx"

# Load sheets
xls = pd.ExcelFile(excel_file)
sprints = pd.read_excel(xls, "SPRINT BACKLOG")
gantt = pd.read_excel(xls, "DIAGRAMA DE GANTT")
product = pd.read_excel(xls, "PRODUCT BACKLOG")
historias = pd.read_excel(xls, "HISTORIAS DE USUARIO")

# Function to replace RF-XXX with HU-XX based on ID PB or the number
def replace_rf_with_hu(row):
    val = str(row['HISTORIA DE USUARIO'])
    if val.startswith('RF-'):
        # Extract number
        num = int(val.split('-')[1])
        return f"HU-{num:02d}"
    return val

if 'HISTORIA DE USUARIO' in sprints.columns:
    sprints['HISTORIA DE USUARIO'] = sprints.apply(replace_rf_with_hu, axis=1)

# Change Gantt days to hours
# Column name seems to be "DURACIÓN (Días)" or similar
duration_col = [c for c in gantt.columns if "DURACIÓN" in c.upper()][0]
gantt[duration_col] = gantt[duration_col] * 8
new_duration_col = duration_col.replace("Días", "Horas").replace("DIAS", "HORAS").replace("días", "horas")
gantt.rename(columns={duration_col: new_duration_col}, inplace=True)

# Add Inventario and Sucursales to Product Backlog and a new Sprint
# Let's get the max PB id
max_pb = 100 # just a guess based on the data
new_pb = [
    {"HISTORIAS DE USUARIO": "HU-106", "TAREAS": "Implementar módulo de Inventario (adaptado de aleslisis)", "PRIORIDAD": "MEDIA", "NOMBRE ASIGNADO": "Agente AI", "RESPONSABILIDAD": "BACKEND / FRONTEND"},
    {"HISTORIAS DE USUARIO": "HU-107", "TAREAS": "Implementar módulo de Sucursales (adaptado de aleslisis)", "PRIORIDAD": "MEDIA", "NOMBRE ASIGNADO": "Agente AI", "RESPONSABILIDAD": "BACKEND / FRONTEND"}
]
product = pd.concat([product, pd.DataFrame(new_pb)], ignore_index=True)

# Save Excel
with pd.ExcelWriter(out_file, engine='openpyxl') as writer:
    sprints.to_excel(writer, sheet_name="SPRINT BACKLOG", index=False)
    product.to_excel(writer, sheet_name="PRODUCT BACKLOG", index=False)
    historias.to_excel(writer, sheet_name="HISTORIAS DE USUARIO", index=False)
    gantt.to_excel(writer, sheet_name="DIAGRAMA DE GANTT", index=False)

print("Updated excel successfully.")
