<?php
/**
 * DashboardRepository — Centraliza las consultas SQL del dashboard y reportes.
 */
class DashboardRepository {

    private PDO $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
    }

    /**
     * Citas agrupadas por mes y estado (últimos 6 meses).
     */
    public function getCitasPorMes(): array {
        return $this->conn->query("
            SELECT TO_CHAR(fecha_hora, 'YYYY-MM') as mes, estado, COUNT(*) as total
              FROM citas
             WHERE fecha_hora >= NOW() - INTERVAL '6 MONTH'
             GROUP BY TO_CHAR(fecha_hora, 'YYYY-MM'), estado
             ORDER BY mes ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ocupación de camas por estado.
     */
    public function getOcupacionCamas(): array {
        return $this->conn->query(
            "SELECT estado, COUNT(*) as total FROM camas GROUP BY estado"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Total de pacientes registrados.
     */
    public function getTotalPacientes(): int {
        return (int) $this->conn->query("SELECT COUNT(*) FROM pacientes")->fetchColumn();
    }

    /**
     * Crecimiento de pacientes en los últimos 6 meses.
     */
    public function getCrecimientoPacientes(): array {
        return $this->conn->query("
            SELECT TO_CHAR(u.creado_en, 'YYYY-MM') as mes, COUNT(p.id) as total
              FROM pacientes p
              JOIN usuarios u ON p.usuario_id = u.id
             WHERE u.creado_en >= NOW() - INTERVAL '6 MONTH'
             GROUP BY TO_CHAR(u.creado_en, 'YYYY-MM')
             ORDER BY mes ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crecimiento interanual de citas.
     */
    public function getCrecimientoInteranualCitas(): array {
        return $this->conn->query("
            SELECT EXTRACT(YEAR FROM fecha_hora) as anio, COUNT(*) as total
              FROM citas
             WHERE EXTRACT(YEAR FROM fecha_hora) >= EXTRACT(YEAR FROM CURRENT_DATE) - 1
             GROUP BY EXTRACT(YEAR FROM fecha_hora)
             ORDER BY anio ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Estadísticas para el reporte PDF gerencial.
     */
    public function getEstadisticasPdf(): array {
        $total_pacientes      = (int) $this->conn->query("SELECT COUNT(*) FROM pacientes")->fetchColumn();
        $citas_total          = (int) $this->conn->query("SELECT COUNT(*) FROM citas")->fetchColumn();
        $citas_completadas    = (int) $this->conn->query("SELECT COUNT(*) FROM citas WHERE estado='completada'")->fetchColumn();
        $citas_canceladas     = (int) $this->conn->query("SELECT COUNT(*) FROM citas WHERE estado='cancelada'")->fetchColumn();

        return [
            'total_pacientes'   => $total_pacientes,
            'citas_total'       => $citas_total,
            'citas_completadas' => $citas_completadas,
            'citas_canceladas'  => $citas_canceladas,
            'efectividad'       => $citas_total > 0 ? round(($citas_completadas / $citas_total) * 100, 2) : 0,
            'tasa_cancelacion'  => $citas_total > 0 ? round(($citas_canceladas  / $citas_total) * 100, 2) : 0,
        ];
    }

    /**
     * Datos para exportación CSV de tendencias de citas.
     */
    public function getDatosCsvTendencias(): array {
        return $this->conn->query("
            SELECT TO_CHAR(fecha_hora, 'YYYY-MM') as mes, estado, COUNT(*) as total
              FROM citas
             GROUP BY TO_CHAR(fecha_hora, 'YYYY-MM'), estado
             ORDER BY mes DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
