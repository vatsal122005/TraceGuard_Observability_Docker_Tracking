# TraceGuard: Full-Stack Observability for Laravel

TraceGuard is a production-ready observability stack designed specifically for Laravel applications. It provides a unified view of **Metrics, Logs, and Traces** (the three pillars of observability) using the industry-standard LGTM stack (Loki, Grafana, Tempo, Mimir/Prometheus).

## 🏗 System Architecture

The project orchestrates a complex observability pipeline using Docker:

- **Laravel App (PHP 8.4)**: The core application instrumented with OpenTelemetry and Prometheus exporters.
- **Prometheus**: Scrapes metrics from the Laravel application and the MySQL exporter.
- **Grafana Loki**: Centralized log aggregation for Laravel logs and Nginx access/error logs.
- **Grafana Tempo**: High-scale distributed tracing backend receiving traces via OTLP.
- **Promtail**: Ships logs from the containerized environment to Loki.
- **MySQL Exporter**: Provides deep visibility into database performance.
- **Grafana**: The visualization layer with pre-configured dashboards for App and Database performance.

## 🛠 Tech Stack

| Component | Technology |
| :--- | :--- |
| **Application** | Laravel 12, PHP 8.4, Nginx |
| **Metrics** | Prometheus, `spatie/laravel-prometheus` |
| **Logs** | Grafana Loki, Promtail |
| **Traces** | Grafana Tempo, OpenTelemetry (OTLP) |
| **Database** | MySQL 8.0 |
| **Cache/Queue** | Redis |
| **Dashboards** | Grafana (Auto-provisioned) |

## 📊 Observability Features

### 1. Unified Metrics
The stack exposes several custom metrics via the `/prometheus` endpoint:

- **HTTP Performance**:
  - `laravel_prometheus_http_requests_total`: Total request count with `method` and `status` labels.
- **Database Performance**:
  - `laravel_prometheus_db_query_duration_seconds`: Histogram of query execution times with `query_type` labels (SELECT, INSERT, UPDATE, etc.).
- **Business/System Metrics**:
  - `Active_Users`: Real-time count of users in the system.
  - `Login_Failures`: Cumulative count of failed authentication attempts.
- **MySQL Health** (via Exporter):
  - `mysql_up`: Binary status of database connectivity.
  - `mysql_global_status_queries`: Total query volume.
  - `mysql_global_status_threads_connected`: Current connection count.

### 2. Distributed Tracing
- Automatic request tracing across the application lifecycle.
- Database query spans to pinpoint slow-performing Eloquent queries.
- Integration with Grafana for seamless transition from metrics to traces.

### 3. Log Aggregation
- Centralized Laravel application logs.
- Nginx access logs for traffic analysis.
- Correlated logs (Trace ID in logs) for drill-down debugging.

## 🚀 Getting Started

### Prerequisites
- Docker and Docker Compose installed.

### Installation

1. Read the [Detailed Working Guide](WORKING_GUIDE.md) to understand the architecture.

2. Clone the repository:
   ```bash
   git clone <repository_url>
   cd TraceGuard_Observability_Docker_Tracking
   ```

2. Start the stack:
   ```bash
   docker-compose up -d --build
   ```

3. Access the services:
   - **Application**: [http://localhost:8006](http://localhost:8006)
   - **Grafana**: [http://localhost:3000](http://localhost:3000) (Default Login: `admin/admin`)
   - **Prometheus**: [http://localhost:9090](http://localhost:9090)

## 📁 Key Project Files

- `app/Providers/ObservabilityServiceProvider.php`: Core logic for OTel tracer registration and Prometheus metric collection.
- `docker-compose.yml`: Infrastructure definition for all observability services.
- `docker/grafana/dashboards/`: JSON definitions for pre-configured Grafana dashboards.
- `docker/prometheus/prometheus.yml`: Scrape configurations for app and database metrics.
- `WORKING_GUIDE.md`: Detailed technical deep-dive into how the observability pipeline works.

## 🛡 Security & Best Practices
- **Dashboard Synchronization**: Dashboards are set to `editable: false` in provisioning to ensure the JSON files remain the single source of truth.
- **Metric Resilience**: Application metrics are stored using a database-backed Prometheus collector to survive container restarts.
- **Exporter Stability**: Uses `prom/mysqld-exporter:v0.15.0` for maximum compatibility with Laravel database configurations.

## 📜 License
This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
