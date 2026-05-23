<template>
  <div class="home-wrap">

    <header class="header">
      <div class="header-inner">
        <div class="header-logo">
          <img src="/images/SBSI-logo.png" alt="SBSI" />
        </div>
        <div class="header-user">
          <div class="user-info">
            <div class="user-avatar">{{ userInitials }}</div>
            <div class="user-details">
              <span class="user-name">{{ userName }}</span>
              <span class="user-role">{{ userRole }}</span>
            </div>
          </div>
          <button class="logout-btn" @click="handleLogout" aria-label="Logout">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16 17 21 12 16 7"/>
              <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <span class="logout-text">Logout</span>
          </button>
        </div>
      </div>
    </header>

    <main class="main">
      <div class="main-inner">

        <div class="greeting reveal">
          <p class="greeting-sub">Good {{ timeOfDay }},</p>
          <h1 class="greeting-name">
            {{ userName }}
            <span class="role-badge">{{ userRole }}</span>
          </h1>
          <p class="greeting-desc">Select a subsystem below to get started.</p>
        </div>

        <div class="cards-grid">
          <div
              class="sys-card reveal"
              v-for="(sys, i) in subsystems"
              :key="sys.title"
              :style="{ transitionDelay: i * 0.1 + 's', '--accent-color': sys.iconColor }"
              @click="openModule(sys.title)"
          >
              <div class="card-header-group">
                <div class="card-icon-wrap" :style="{ background: sys.iconBg }">
                    <svg :viewBox="sys.viewBox" width="24" height="24" fill="none" :stroke="sys.iconColor" stroke-width="1.8" v-html="sys.icon"></svg>
                </div>
                <h3>{{ sys.title }}</h3>
              </div>

              <div class="card-body">
                <p>{{ sys.desc }}</p>
              </div>

              <div class="card-footer">
                <div class="action-link">
                  <span>Open module</span>
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                  </svg>
                </div>
              </div>
          </div>
        </div>

      </div>
    </main>

  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Load user from localStorage
const user = ref(JSON.parse(localStorage.getItem('user') || '{}'))

const userName = computed(() => {
  if (user.value.profile) {
    const first = user.value.profile.first_name || ''
    const last = user.value.profile.last_name || ''
    if (first || last) {
      return `${first} ${last}`.trim()
    }
  }
  return user.value.name || user.value.email || 'User'
})

const userRole = computed(() => {
  return user.value.profile?.role?.name || user.value.role || 'User'
})

const userInitials = computed(() => {
  if (user.value.profile) {
    const first = user.value.profile.first_name || ''
    const last = user.value.profile.last_name || ''
    if (first || last) {
      const firstInitial = first.substring(0, 1).toUpperCase()
      const lastInitial = last.substring(0, 1).toUpperCase()
      return `${firstInitial}${lastInitial}`
    }
  }
  const name = userName.value
  if (name) {
    const parts = name.split(' ')
    if (parts.length > 1) {
      return `${parts[0][0]}${parts[1][0]}`.toUpperCase()
    }
    return name.substring(0, 2).toUpperCase()
  }
  return 'US'
})

const timeOfDay = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'morning'
  if (h < 18) return 'afternoon'
  return 'evening'
})

const subsystems = computed(() => {
  const base = [
    {
      title: 'Contract Management',
      desc: 'Track, manage, and renew contracts with full audit trails and automated approval workflows — all in one place.',
      iconBg: '#EBF3FC', 
      iconColor: '#2E85D8',
      viewBox: '0 0 24 24',
      icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'
    },
    {
      title: 'Smart Expense Reimbursement',
      desc: 'Submit and track expense reimbursements with automated approval workflows and real-time status updates.',
      iconBg: '#EAF9F0',
      iconColor: '#27ae60',
      viewBox: '0 0 24 24',
      icon: '<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>'
    },
    {
      title: 'Productivity Report System',
      desc: 'Generate detailed productivity analytics and performance summaries across teams with exportable insights.',
      iconBg: '#F4EDF8',
      iconColor: '#8e44ad',
      viewBox: '0 0 24 24',
      icon: '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'
    },
    {
      title: 'Ticketing System',
      desc: 'Submit, escalate, and resolve support tickets with SLA breach monitoring and priority management tools.',
      iconBg: '#FDF1E9',
      iconColor: '#d35400',
      viewBox: '0 0 24 24',
      icon: '<path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>'
    },
  ]

  if (userRole.value === 'IT Admin') {
    base.push({
      title: 'User & Access Management',
      desc: 'Manage users, assign roles, define permissions, and configure departments for the entire organization.',
      iconBg: '#FCEBEB',
      iconColor: '#E74C3C',
      viewBox: '0 0 24 24',
      icon: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'
    })
  }

  return base
})

const handleLogout = () => {
  localStorage.removeItem('access_token')
  localStorage.removeItem('session_id')
  localStorage.removeItem('user')
  router.push('/')
}

const openModule = (subsystemTitle: string) => {
    if (subsystemTitle === 'Contract Management') {
      if (userRole.value === 'IT Admin') {
        router.push('/admin')
      } else if (userRole.value === 'Admin') {
        window.location.href = '/crms/admin/dashboard'
      } else if (userRole.value === 'Manager' || userRole.value === 'Finance Manager') {
        window.location.href = '/crms/manager/dashboard'
      } else if (userRole.value === 'Sales' || userRole.value === 'Employee' || userRole.value === 'Finance Employee' || userRole.value === 'Finance') {
        window.location.href = '/crms/sales/dashboard'
      }
    } else if (subsystemTitle === 'User & Access Management') {
    router.push('/admin')
  } else {
    alert(`${subsystemTitle} is not active in this development environment.`)
  }
}

// Pure visual scroll trigger bindings
onMounted(() => {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) entry.target.classList.add('visible')
    })
  }, { threshold: 0.1 })

  document.querySelectorAll('.reveal').forEach(el => observer.observe(el))
})
</script>

<style scoped>
* { box-sizing: border-box; margin: 0; padding: 0; }

.home-wrap {
  min-height: 100vh;
  background: #F4F7FC; 
  font-family: 'Poppins', 'Montserrat', sans-serif;
  color: #33334e;
}

/* ── HEADER ── */
.header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: #2F2F7E; 
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.header-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 70px;
}
.header-logo img { 
  height: 44px; 
  object-fit: contain; 
}

.header-user { 
  display: flex; 
  align-items: center; 
  gap: 12px; 
}

.user-info { 
  display: flex; 
  align-items: center; 
  gap: 8px; 
}
.user-avatar {
  width: 34px; height: 34px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15); 
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; 
  color: #FFFFFF; 
  border: 1px solid rgba(255, 255, 255, 0.25);
  flex-shrink: 0;
}
.user-details { 
  display: flex; 
  flex-direction: column; 
}
.user-name { 
  font-size: 13px; 
  font-weight: 600; 
  color: #FFFFFF; 
  line-height: 1.2; 
} 
.user-role { 
  font-size: 11px; 
  color: rgba(255, 255, 255, 0.5); 
} 

.logout-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 4px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: rgba(255, 255, 255, 0.8);
  padding: 8px 12px; border-radius: 8px;
  cursor: pointer; transition: all 0.2s;
}
.logout-btn:hover { 
  background: rgba(239, 68, 68, 0.2); 
  border-color: rgba(239, 68, 68, 0.4); 
  color: #FEB2B2; 
}

/* ── MAIN WORKSPACE ── */
.main { padding: 40px 24px 60px; }
.main-inner { max-width: 1200px; margin: 0 auto; }

/* ── GREETING ── */
.greeting { 
  margin-bottom: 40px; 
}
.greeting-sub { 
  font-size: 14px; 
  color: #64748B; 
  margin-bottom: 6px; 
}
.greeting-name {
  font-size: clamp(24px, 5vw, 36px);
  font-weight: 700;
  color: #252578;
  margin-bottom: 8px;
  display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
}
.role-badge {
  font-size: 11px; font-weight: 600;
  background: rgba(46, 133, 216, 0.08);
  border: 1px solid rgba(46, 133, 216, 0.2);
  color: #2E85D8;
  padding: 2px 10px; border-radius: 20px;
}
.greeting-desc { font-size: 14px; color: #64748B; }

/* ── CARDS GRID ── */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
}

.sys-card {
  position: relative;
  background: #FFFFFF; 
  border: 1px solid #E2E8F0;
  border-left: 4px solid var(--accent-color);
  border-radius: 16px;
  padding: 28px 28px 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  cursor: pointer;
  transition: border-color 0.3s, transform 0.3s, box-shadow 0.3s;
}
.sys-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(37, 37, 120, 0.06);
}
.sys-card:hover .action-link {
  color: #1a6dbf;
}
.sys-card:hover .action-link svg {
  transform: translateX(4px);
}

.card-header-group {
  display: flex;
  align-items: center;
  gap: 16px;
}

.card-icon-wrap {
  width: 48px; height: 48px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.card-header-group h3 { 
  font-size: 20px; 
  font-weight: 700; 
  color: #0A1629; 
  line-height: 1.2;
}

.card-body { 
  flex: 1; 
}
.card-body p { 
  font-size: 14px; 
  color: #5C6F84; 
  line-height: 1.6; 
}

/* FOOTER SECTION: Line separator + action */
.card-footer {
  margin-top: 12px;
  padding-top: 16px;
  border-top: 1px solid #EDF2F7; 
  display: flex;
  justify-content: flex-end;
}

.action-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #2E85D8;
  font-weight: 600;
  font-size: 14px;
  transition: color 0.2s;
}
.action-link svg {
  transition: transform 0.2s;
}

/* Scroll Reveal base animations styles */
.reveal {
  opacity: 0;
  transform: translateY(20px);
  transition: opacity 0.6s ease-out, transform 0.6s ease-out;
}
.reveal.visible {
  opacity: 1;
  transform: translateY(0);
}

/* ── RESPONSIVE MEDIA QUERIES ── */
@media (max-width: 768px) {
  .header-inner { padding: 0 16px; }
  .header-logo img { height: 36px; }
  .user-details { display: none; }
  .logout-text { display: none; }
  .logout-btn { padding: 8px; }
  
  .main { padding: 32px 16px 60px; }
  .greeting { margin-bottom: 32px; }
  .greeting-name { gap: 8px; }

  .cards-grid { 
    grid-template-columns: 1fr; 
    gap: 16px;
  }
  .sys-card {
    padding: 20px 20px 16px;
  }
  .card-header-group h3 {
    font-size: 18px;
  }
}
</style>