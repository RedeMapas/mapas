{{/*
Expand the name of the chart.
*/}}
{{- define "mapas.name" -}}
{{- default .Chart.Name .Values.nameOverride | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Create a default fully qualified app name.
*/}}
{{- define "mapas.fullname" -}}
{{- if .Values.fullnameOverride }}
{{- .Values.fullnameOverride | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- $name := default .Chart.Name .Values.nameOverride }}
{{- if contains $name .Release.Name }}
{{- .Release.Name | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- printf "%s-%s" .Release.Name $name | trunc 63 | trimSuffix "-" }}
{{- end }}
{{- end }}
{{- end }}

{{/*
Create chart name and version as used by the chart label.
*/}}
{{- define "mapas.chart" -}}
{{- printf "%s-%s" .Chart.Name .Chart.Version | replace "+" "_" | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Common labels
*/}}
{{- define "mapas.labels" -}}
helm.sh/chart: {{ include "mapas.chart" . }}
{{ include "mapas.selectorLabels" . }}
{{- if .Chart.AppVersion }}
app.kubernetes.io/version: {{ .Chart.AppVersion | quote }}
{{- end }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end }}

{{/*
Selector labels
*/}}
{{- define "mapas.selectorLabels" -}}
app.kubernetes.io/name: {{ include "mapas.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
{{- end }}

{{/*
Create the name of the service account to use
*/}}
{{- define "mapas.serviceAccountName" -}}
{{- if .Values.serviceAccount.create }}
{{- default (include "mapas.fullname" .) .Values.serviceAccount.name }}
{{- else }}
{{- default "default" .Values.serviceAccount.name }}
{{- end }}
{{- end }}

{{/*
Return the PostgreSQL hostname
*/}}
{{- define "mapas.postgresql.host" -}}
{{- if .Values.postgresql.enabled }}
{{- printf "%s-postgresql" (include "mapas.fullname" .) }}
{{- else }}
{{- .Values.mapas.database.host }}
{{- end }}
{{- end }}

{{/*
Return the PostgreSQL port
*/}}
{{- define "mapas.postgresql.port" -}}
{{- if .Values.postgresql.enabled }}
{{- printf "5432" }}
{{- else }}
{{- .Values.mapas.database.port | default "5432" }}
{{- end }}
{{- end }}

{{/*
Return the PostgreSQL database name (groundhog2k postgres chart)
*/}}
{{- define "mapas.postgresql.database" -}}
{{- if .Values.postgresql.enabled }}
{{- .Values.postgresql.userDatabase.name }}
{{- else }}
{{- .Values.mapas.database.name }}
{{- end }}
{{- end }}

{{/*
Return the PostgreSQL username (groundhog2k postgres chart)
*/}}
{{- define "mapas.postgresql.username" -}}
{{- if .Values.postgresql.enabled }}
{{- .Values.postgresql.settings.superuser | default "mapas" }}
{{- else }}
{{- .Values.mapas.database.user }}
{{- end }}
{{- end }}

{{/*
Return the PostgreSQL secret name (groundhog2k postgres chart uses fullname for secret)
*/}}
{{- define "mapas.postgresql.secretName" -}}
{{- if .Values.postgresql.enabled }}
{{- printf "%s-postgresql" (include "mapas.fullname" .) }}
{{- else }}
{{- if .Values.mapas.database.existingSecret }}
{{- .Values.mapas.database.existingSecret }}
{{- else }}
{{- printf "%s-db-secret" (include "mapas.fullname" .) }}
{{- end }}
{{- end }}
{{- end }}

{{/*
Return the Redis cache hostname (PHP Redis connect uses default port 6379)
*/}}
{{- define "mapas.redis.cache.host" -}}
{{- if index .Values "redis-cache" "enabled" }}
{{- printf "%s-redis-cache" (include "mapas.fullname" .) }}
{{- else }}
{{- .Values.mapas.redisCache.host }}
{{- end }}
{{- end }}

{{/*
Return the Redis sessions hostname
*/}}
{{- define "mapas.redis.sessions.host" -}}
{{- if index .Values "redis-sessions" "enabled" }}
{{- printf "%s-redis-sessions" (include "mapas.fullname" .) }}
{{- else }}
{{- .Values.mapas.sessions.savePath }}
{{- end }}
{{- end }}

{{/*
Return the sessions save path
*/}}
{{- define "mapas.sessions.savePath" -}}
{{- if index .Values "redis-sessions" "enabled" }}
{{- printf "tcp://%s-redis-sessions:6379" (include "mapas.fullname" .) }}
{{- else }}
{{- .Values.mapas.sessions.savePath }}
{{- end }}
{{- end }}
