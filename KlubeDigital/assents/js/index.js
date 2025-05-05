// Mobile Menu Toggle
const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
const nav = document.querySelector('nav');

mobileMenuBtn.addEventListener('click', () => {
    nav.classList.toggle('active');
    mobileMenuBtn.classList.toggle('active');
});

// Header Scroll Effect
const header = document.getElementById('header');
window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
});

// Scroll to section when clicking on nav links
const navLinks = document.querySelectorAll('nav a');
navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const target = document.querySelector(link.getAttribute('href'));
        window.scrollTo({
            top: target.offsetTop - 80,
            behavior: 'smooth'
        });
        nav.classList.remove('active');
    });
});

// Service Cards Animation
const serviceCards = document.querySelectorAll('.service-card');
const whyUsImage = document.querySelector('.why-us-image');

function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.bottom >= 0
    );
}

function animateOnScroll() {
    serviceCards.forEach((card, index) => {
        if (isInViewport(card)) {
            setTimeout(() => {
                card.classList.add('animated');
            }, index * 100);
        }
    });

    if (isInViewport(whyUsImage)) {
        whyUsImage.classList.add('animated');
    }
}

window.addEventListener('scroll', animateOnScroll);
window.addEventListener('load', animateOnScroll);

// Testimonials Slider
const testimonialSlides = document.querySelectorAll('.testimonial-slide');
const dots = document.querySelectorAll('.dot');
let currentSlide = 0;

function showSlide(index) {
    testimonialSlides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    testimonialSlides[index].classList.add('active');
    dots[index].classList.add('active');
    currentSlide = index;
}

dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
        showSlide(index);
    });
});

// Auto slide testimonials
setInterval(() => {
    currentSlide = (currentSlide + 1) % testimonialSlides.length;
    showSlide(currentSlide);
}, 5000);

// Form Submission com integração ao sistema de contatos
const contactForm = document.getElementById('contact-form');
if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        // Desabilitar o botão durante o envio
        const submitButton = contactForm.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Enviando...';
        
        const formData = new FormData(contactForm);
        
        fetch('salvar-contato.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Exibir mensagem de sucesso
                contactForm.innerHTML = `
                    <div style="text-align: center; padding: 30px 0;">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <h3 style="margin: 20px 0; color: var(--secondary);">Mensagem enviada com sucesso!</h3>
                        <p>Obrigado pelo seu contato. Sua solicitação foi registrada com o código <strong>${data.id}</strong>. Nossa equipe entrará em contato em breve.</p>
                    </div>
                `;
            } else {
                // Exibir mensagem de erro
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                errorMessage.textContent = data.message || 'Ocorreu um erro ao enviar sua mensagem. Por favor, tente novamente.';
                errorMessage.style.color = 'red';
                errorMessage.style.marginTop = '10px';
                
                // Remover mensagem de erro anterior, se existir
                const previousError = contactForm.querySelector('.error-message');
                if (previousError) {
                    previousError.remove();
                }
                
                contactForm.appendChild(errorMessage);
                
                // Reativar o botão
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            
            // Exibir mensagem de erro
            const errorMessage = document.createElement('div');
            errorMessage.className = 'error-message';
            errorMessage.textContent = 'Ocorreu um erro de comunicação. Por favor, verifique sua conexão e tente novamente.';
            errorMessage.style.color = 'red';
            errorMessage.style.marginTop = '10px';
            
            // Remover mensagem de erro anterior, se existir
            const previousError = contactForm.querySelector('.error-message');
            if (previousError) {
                previousError.remove();
            }
            
            contactForm.appendChild(errorMessage);
            
            // Reativar o botão
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        });
    });
}

// Funções utilitárias para manipulação de contatos (podem ser usadas no admin ou outras seções)
function formatarTelefone(telefone) {
    // Remove todos os caracteres não numéricos
    const apenasNumeros = telefone.replace(/\D/g, '');
    
    // Adiciona prefixo do Brasil se não existir
    const telefoneCompleto = apenasNumeros.startsWith('55') 
      ? apenasNumeros 
      : `55${apenasNumeros}`;
      
    return telefoneCompleto;
}

function gerarLinkWhatsApp(telefone, nome, id, mensagem) {
    const telefoneFormatado = formatarTelefone(telefone);
    const mensagemBase = `Olá ${nome}, referente ao seu atendimento ${id} na Klube Digital. ${mensagem}`;
    const mensagemCodificada = encodeURIComponent(mensagemBase);
    
    return `https://api.whatsapp.com/send?phone=${telefoneFormatado}&text=${mensagemCodificada}`;
}

// Formatador de data para apresentação
function formatarData(dataISO) {
    if (!dataISO) return '';
    
    const data = new Date(dataISO);
    const dia = data.getDate().toString().padStart(2, '0');
    const mes = (data.getMonth() + 1).toString().padStart(2, '0');
    const ano = data.getFullYear();
    const hora = data.getHours().toString().padStart(2, '0');
    const minuto = data.getMinutes().toString().padStart(2, '0');
    
    return `${dia}/${mes}/${ano} às ${hora}:${minuto}`;
}

// Códigos de status para apresentação
const statusLabels = {
    'pendente': 'Pendente',
    'em_atendimento': 'Em Atendimento',
    'concluido': 'Concluído',
    'cancelado': 'Cancelado'
};

// Se você precisar carregar a lista de contatos em algum lugar (como área administrativa)
function carregarContatos(callback) {
    fetch('dados/contatos.json')
        .then(response => response.json())
        .then(data => {
            if (callback && typeof callback === 'function') {
                callback(data);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar contatos:', error);
        });
}

// Exemplo de função para exibir contatos em uma tabela
function exibirContatosEmTabela(dados, elementoDestino) {
    if (!dados || !dados.contatos || !dados.contatos.length) {
        elementoDestino.innerHTML = '<p>Nenhum contato encontrado.</p>';
        return;
    }
    
    let html = `
        <table class="contatos-tabela">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data</th>
                    <th>Nome</th>
                    <th>Clínica</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    dados.contatos.forEach(contato => {
        html += `
            <tr>
                <td>${contato.id}</td>
                <td>${formatarData(contato.data)}</td>
                <td>${contato.nome}</td>
                <td>${contato.clinica}</td>
                <td><span class="status-${contato.status}">${statusLabels[contato.status] || contato.status}</span></td>
                <td>
                    <a href="${contato.whatsapp_link}" target="_blank" class="btn-whatsapp">WhatsApp</a>
                    <button class="btn-ver-mais" data-id="${contato.id}">Ver mais</button>
                </td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    elementoDestino.innerHTML = html;
    
    // Adicionar event listeners aos botões
    document.querySelectorAll('.btn-ver-mais').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const contato = dados.contatos.find(c => c.id === id);
            if (contato) {
                exibirDetalheContato(contato);
            }
        });
    });
}

// Função para exibir os detalhes de um contato
function exibirDetalheContato(contato) {
    // Exemplo de modal para exibir detalhes
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Detalhes do Contato ${contato.id}</h2>
            <div class="contato-info">
                <p><strong>Data:</strong> ${formatarData(contato.data)}</p>
                <p><strong>Nome:</strong> ${contato.nome}</p>
                <p><strong>Email:</strong> ${contato.email}</p>
                <p><strong>Telefone:</strong> ${contato.telefone}</p>
                <p><strong>Clínica:</strong> ${contato.clinica}</p>
                <p><strong>Status:</strong> ${statusLabels[contato.status] || contato.status}</p>
                <p><strong>Mensagem:</strong></p>
                <div class="mensagem-box">${contato.mensagem}</div>
                <div class="actions">
                    <a href="${contato.whatsapp_link}" target="_blank" class="btn-whatsapp">Responder via WhatsApp</a>
                    <button class="btn-atualizar-status" data-id="${contato.id}">Atualizar Status</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Fechar modal
    modal.querySelector('.close').addEventListener('click', () => {
        modal.remove();
    });
    
    // Fechar ao clicar fora do conteúdo
    modal.addEventListener('click', e => {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// Se você estiver em uma página de administração, pode carregar os contatos
document.addEventListener('DOMContentLoaded', function() {
    const adminContatos = document.getElementById('admin-contatos');
    if (adminContatos) {
        carregarContatos(function(dados) {
            exibirContatosEmTabela(dados, adminContatos);
        });
    }
});