CREATE TABLE paciente (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    nome_paciente VARCHAR(100),
    telefone_paciente VARCHAR(15),
    endereco_paciente VARCHAR(100),
    nascimento_paciente DATE,
    CPF_paciente VARCHAR(11) UNIQUE
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE medico (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    nome_medico VARCHAR(100),
    especialidade_medico VARCHAR(100),
    CPF_medico VARCHAR(11) UNIQUE,
    CRM_medico VARCHAR(20) UNIQUE,
    telefone_medico VARCHAR(15),
    endereco_medico VARCHAR(100)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE consultas (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    data_consulta DATE,
    horario TIME,
    CPF_paciente VARCHAR(11),
    CRM_medico VARCHAR(20),
    status_consulta ENUM('Agendada', 'Realizada', 'Cancelada') DEFAULT 'Agendada',
    FOREIGN KEY (CPF_paciente) REFERENCES paciente(CPF_paciente),
    FOREIGN KEY (CRM_medico) REFERENCES medico(CRM_medico)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE usuario (
    ID INT PRIMARY KEY AUTO_INCREMENT, 
    nome_usuario VARCHAR(50),
    senha_usuario VARCHAR(100), 
    email_usuario VARCHAR(100), 
    codigo2etapas VARCHAR(10), 
    tipo_usuario VARCHAR(20)
) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
