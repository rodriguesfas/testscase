FROM php:8.2-cli

# Instalar extensões necessárias
RUN apt-get update && apt-get install -y \
    libpq-dev \
    gcc \
    g++ \
    nodejs \
    npm \
    default-jdk \
    golang \
    git \
    wget \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Rust
RUN curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh -s -- -y
ENV PATH="/root/.cargo/bin:${PATH}"

# Configurar diretório de trabalho
WORKDIR /app

# Copiar arquivos
COPY . .

# Manter o container rodando
CMD ["tail", "-f", "/dev/null"]
