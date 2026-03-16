# TraceGuard: Technical Working Guide

This guide provides a deep-dive into the internal mechanics of the TraceGuard observability stack. It explains how data flows from your Laravel application to the visualization dashboards.

---

## 1. Metrics Pipeline (Prometheus)

The metrics pipeline tracks "what is happening" in numeric form over time.

### A. Collection (Application Level)
- **Library**: Uses `spatie/laravel-prometheus`.
- **Instrumentation**: Registered in `app/Providers/ObservabilityServiceProvider.php`.
- **Storage**: Unlike standard Prometheus exporters that run a long-lived process, Laravel is short-lived. To persist counters (like `Active_Users`) between requests, we use the `database` driver for Prometheus (configured in `config/prometheus.php`).
- **Endpoint**: The application exposes a `/prometheus` route which renders the current state of all registered collectors in the Prometheus text format.

### B. Collection (Infrastructure Level)
- **MySQL Exporter**: A standalone Go-based binary (`prom/mysqld-exporter`) connects to the MySQL container using a `.my.cnf` file. It translates MySQL internal stats (like `Threads_connected`) into Prometheus metrics on port `9104`.

### C. Scraping
- **Prometheus Server**: Every 10-15 seconds (configured in `prometheus.yml`), Prometheus makes an HTTP request to:
  - `http://app:80/prometheus`
  - `http://mysql-exporter:9104/metrics`
- It stores this time-series data in its internal database.

---

## 2. Logging Pipeline (Loki)

The logging pipeline records "why something happened" through text-based events.

### A. Shipment (Promtail)
- **Promtail**: A lightweight agent that runs as a sidecar container.
- **Mounts**: It has read-only access to the host's log directories:
  - `./storage/logs/` (Laravel application logs)
  - `./docker/nginx/logs/` (Nginx access and error logs)
- **Tagging**: Promtail attaches metadata (labels) like `job="laravel-logs"` or `job="nginx-access"` to each log line.

### B. Aggregation (Loki)
- Promtail pushes the tagged logs to **Grafana Loki** on port `3100`. Loki indexes the labels but compresses the log content, making it extremely cost-effective.

---

## 3. Distributed Tracing (Tempo)

The tracing pipeline follows the "path of a request" across the system.

### A. Instrumentation
- **OpenTelemetry SDK**: Initialized in `ObservabilityServiceProvider.php`.
- **Middleware**: `OpenTelemetryMiddleware` starts a root span for every incoming HTTP request. It captures the URL, method, and response status.
- **DB Listener**: The `ObservabilityServiceProvider` listens to `DB::listen` events. For every SQL query, it starts a child span, captures the SQL statement, and attaches it to the parent HTTP span.

### B. Exporting (OTLP)
- The SDK sends traces in batches using the **OTLP (OpenTelemetry Line Protocol)** over HTTP directly to **Grafana Tempo** on port `4318`.

---

## 4. Visualization (Grafana)

Grafana is the "glass" that brings everything together.

### A. Data Sources
Grafana is pre-configured (via `docker/grafana/datasources.yml`) to connect to:
1. **Prometheus**: For graph panels.
2. **Loki**: For log exploration and "Log to Trace" correlation.
3. **Tempo**: For inspecting trace Gantt charts.

### B. Dashboard Provisioning
- Dashboards are defined as JSON files in `docker/grafana/dashboards/`.
- **Synchronization**: We set `editable: false` in the dashboard provider. This means if you want to change a dashboard permanently, you **must** edit the JSON file and increment the `version` number. Grafana will then overwrite its internal state with your file-based definition upon restart.

---

## 5. Correlation: The "Secret Sauce"

The real power of this stack is **Trace Correlation**:
1. You see a spike in latency in a **Prometheus** graph.
2. You click on a data point and "Explore" the **Loki** logs for that timeframe.
3. Because the `Trace ID` is injected into the logs (and spans), Loki can provide a "Tempo" link next to log lines.
4. Clicking that link opens the exact **Tempo** trace, showing you the slow SQL query that caused the original latency spike.
