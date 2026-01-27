# RFP TENDER DOCUMENTATION
## Visitor Management System - UCBL
## Demo-to-Production Upgrade Project

---

**Package Version:** 2.0  
**Created:** January 26, 2026  
**RFP Reference:** VMS/UCBL/RFP/2026/001

---

### OVERVIEW

This package contains all necessary documentation for the Request for Proposal (RFP) process for **upgrading the existing VMS demo to full production deployment** and implementing advanced features for United Commercial Bank Limited (UCBL).

**IMPORTANT: This is NOT a greenfield project.** UCBL has already developed a comprehensive VMS demo system with modern technology stack. The RFP is for upgrading this demo to enterprise-grade production deployment across 500+ branches.

---

### CURRENT DEMO SYSTEM STATUS

**Technology Stack:**
- **Backend:** PHP 8.4, Laravel 12, Reverb/WebSockets
- **Web Server:** Nginx, Docker Swarm, Ubuntu Server
- **Database:** MySQL with Redis caching
- **Monitoring:** Prometheus, Grafana, Loki (to be implemented in production)

**Currently Implemented Features:**
✅ Live Visitor Dashboard with real-time updates via WebSockets  
✅ Role-Based Access Control (RBAC)  
✅ Full Authentication and Authorization  
✅ Two-Factor Authentication (2FA) with SMS and Email  
✅ RFID card support and management  
✅ Visitor registration and management  
✅ Check-in/check-out system  
✅ CSV export for visitor lists  
✅ Email notifications to hosts with visitor lists  
✅ SMS notifications for alerts and 2FA  
✅ Multi-language support (English, Bengali basics)  
✅ Responsive design for all devices  
✅ Admin panel with comprehensive management features  

**Features to be Implemented in Production:**
⏳ QR code-based check-in (alternative to RFID)  
⏳ Face recognition integration  
⏳ LDAP/Active Directory integration  
⏳ HR system integration  
⏳ Advanced reporting (PDF/Excel export)  
⏳ Enhanced monitoring (Prometheus, Grafana, Loki)  
⏳ Container orchestration (Kubernetes)  
⏳ Load balancing and auto-scaling  
⏳ Production-grade security hardening  

---

### DOCUMENT PACKAGE CONTENTS

This package includes the following documents:

#### 1. **VMSUCBL_RFP_Document.md**
**Purpose:** Main Request for Proposal document for demo-to-production upgrade

**Contents:**
- Current demo system overview and capabilities
- Production upgrade requirements
- Scope of work (enhancing existing features, implementing new features)
- Technical specifications (current stack and production requirements)
- Deliverables (9 phases, 32 weeks)
- Eligibility criteria for vendors
- Proposal requirements
- Evaluation criteria
- Submission requirements
- Terms and conditions

**Key Sections:**
- Existing demo technology stack (PHP 8.4, Laravel 12, Reverb)
- Production architecture requirements (Kubernetes, Prometheus, Grafana, Loki)
- Core features already implemented vs. features to be added
- Security features (2FA, RBAC, encryption)
- Integration requirements (LDAP, HR, Email, SMS)
- Performance requirements (2,000 concurrent users, 99.9% uptime)
- Compliance with Bangladesh Bank regulations

---

#### 2. **01_Evaluation_Scorecard.md**
**Purpose:** Standardized scoring rubric for evaluating vendor proposals

**Contents:**
- Instructions to evaluators
- Six evaluation sections with detailed scoring criteria:
  - Technical Solution (30%)
  - Company Experience (20%)
  - Team Qualifications (15%)
  - Pricing (20%)
  - Implementation Timeline (10%)
  - Post-Sales Support (5%)
- Score descriptions for each criterion
- Qualitative assessment section
- Recommendation section
- Signatures for evaluators and committee

**Usage:**
- Print one copy per evaluator
- Score each proposal independently
- Provide specific comments for low scores
- Sign and date evaluation

---

#### 3. **02_Non_Disclosure_Agreement.md**
**Purpose:** Legal agreement to protect UCBL's confidential information during RFP process

**Contents:**
- Parties to agreement
- Purpose and definitions
- Types of confidential information covered (including demo source code access)
- Vendor obligations (non-disclosure, security measures, etc.)
- Term and termination provisions
- Return of materials requirements
- Intellectual property rights
- Data protection requirements
- Governing law and dispute resolution

**Key Terms:**
- 2-year agreement term
- 5-year survival period for confidentiality obligations
- Compliance with Bangladesh Cyber Security Act 2023
- Data breach notification within 24 hours
- Arbitration in Bangladesh

**Usage:**
- Must be signed by vendor before accessing demo source code
- One original signed copy required
- No confidential information should be shared without this agreement

---

#### 4. **03_Technical_Specifications.md**
**Purpose:** Detailed technical requirements for VMS demo-to-production upgrade

**Contents:**
- **Current Demo Technology Stack:**
  - PHP 8.4, Laravel 12, Reverb/WebSockets
  - Nginx, Docker Swarm, Ubuntu Server
  - MySQL, Redis
  - Current frontend implementation (Blade, Alpine.js, Tailwind)

- **Production Upgrade Requirements:**
  - Server specifications (16+ cores, 64GB RAM, 2TB NVMe)
  - Kubernetes orchestration (recommended over Docker Swarm)
  - Prometheus, Grafana, Loki monitoring setup
  - Network requirements (1 Gbps inbound, 2 Gbps outbound)
  - Security requirements (TLS 1.3, AES-256 encryption)
  - Face recognition specifications (>95% accuracy)
  - RFID system requirements (Mifare Classic 1K/DESFire)
  - QR code system requirements (new feature)
  - Backup requirements (automated, 30-day retention)
  - Performance requirements (99.9% uptime, <2s response)
  - Integration requirements (LDAP, HR, Email, SMS)
  - Compliance requirements (Bangladesh Cyber Security Act 2023)

**Key Specifications:**
- Support for 2,000 concurrent users (500+ branches)
- 99.9% uptime requirement
- Face recognition accuracy > 95%
- TLS 1.3 and AES-256 encryption
- Compliance with Bangladesh Cyber Security Act 2023
- Multi-language support (English and Bengali)
- Real-time monitoring with Prometheus, Grafana, Loki

**Usage:**
- Reference when preparing technical proposals
- Ensure all technical requirements are addressed
- Provide justifications for any alternative approaches
- Demonstrate understanding of current demo architecture

---

#### 5. **04_Contract_Agreement.md**
**Purpose:** Draft contract agreement for selected vendor

**Contents:**
- Parties and recitals (highlighting existing demo system)
- Definitions and interpretation
- Scope of services (upgrade demo to production)
- Deliverables with schedule (9 phases, 32 weeks)
- Compensation and payment terms
- Intellectual property rights (all IP to UCBL)
- Confidentiality
- Warranties and representations
- Indemnification
- Limitation of liability
- Warranty provisions (12-month warranty)
- Training and support (6-month included support)
- Term and termination
- Force majeure
- Insurance requirements
- Independent contractor status
- Compliance with laws
- Dispute resolution
- General provisions
- Signatures
- Exhibits (acceptance criteria, deliverables, scope, pricing)

**Key Contract Terms:**
- Total contract price in Bangladeshi Taka (BDT)
- Payment schedule: 20% advance, 30% core features, 30% integration, 15% pilot, 15% final
- 12-month warranty period
- 6-month included support
- All IP rights to UCBL
- Arbitration in Bangladesh
- Professional liability insurance required (BDT 1 Crore minimum)
- Project duration: 32 weeks (8 months) - reduced from greenfield timeline

**Updated Deliverables Schedule:**
- Phase 1: Demo Assessment (1 week)
- Phase 2: Production Architecture Design (2 weeks)
- Phase 3: Core Feature Upgrade (6 weeks)
- Phase 4: Integration & Optimization (4 weeks)
- Phase 5: Face Recognition Implementation (4 weeks)
- Phase 6: Testing & QA (3 weeks)
- Phase 7: Pilot Implementation (3 weeks)
- Phase 8: Full Rollout (6 weeks)
- Phase 9: Training & Handover (3 weeks)

**Usage:**
- Use as basis for final contract negotiations
- Customize exhibits as needed
- Ensure all legal terms are acceptable to both parties

---

### DOCUMENT USAGE GUIDE

#### For Vendors

1. **Understand the project context:**
   - This is a demo-to-production upgrade, NOT a greenfield project
   - Review current demo features and technology stack
   - Focus on enhancement and scaling, not reimplementation

2. **Sign NDA** before accessing demo source code
3. **Review the demo system** (access will be provided after NDA)
4. **Prepare proposal** addressing:
   - How you will leverage existing demo code
   - Production architecture enhancements
   - Feature upgrades and additions
   - Scaling to 2,000 concurrent users
   - Monitoring and logging implementation
   - Integration with UCBL systems
5. **Use technical specifications** as detailed requirements reference
6. **Submit complete proposal** including:
   - Cover letter (on company letterhead)
   - Company profile
   - Technical proposal (demo assessment + upgrade plan)
   - Commercial proposal
   - Implementation plan (leverage existing demo)
   - Team CVs (Laravel/PHP expertise required)
   - Legal documents (trade license, VAT, tax clearance)
   - Demo/prototype (optional but recommended)

#### For UCBL Evaluation Committee

1. **Use evaluation scorecard** for consistent scoring
2. **Score independently** without influence from other evaluators
3. **Provide detailed comments** for low scores
4. **Maintain objectivity** throughout the process
5. **Evaluate vendor's understanding** of existing demo system
6. **Assess ability to upgrade** vs. rebuild from scratch
7. **Complete qualitative assessment** section
8. **Sign and date** the evaluation

#### For UCBL Procurement Department

1. **Send RFP package** to potential vendors
2. **Provide demo access** after NDA signing
3. **Collect proposals** by closing date
4. **Verify completeness** of all required documents
5. **Distribute to evaluation committee** after collection
6. **Use evaluation scorecards** for consistent assessment
7. **Rank proposals** based on total weighted scores
8. **Select highest-evaluated responsive bidder**
9. **Use contract agreement draft** for contract negotiations
10. **Ensure all legal documents** are signed before award

---

### SUBMISSION CHECKLIST

#### Vendor Submission Checklist

Before submitting your proposal, ensure you have:

- [ ] Signed Non-Disclosure Agreement
- [ ] Cover letter on company letterhead
- [ ] Company profile with financial statements
- [ ] Technical proposal including:
  - [ ] Demo system assessment
  - [ ] Production upgrade plan
  - [ ] Leveraging existing code strategy
  - [ ] Technology stack justification
  - [ ] Security and compliance approach
- [ ] Commercial proposal with detailed pricing
- [ ] Implementation plan (32 weeks, leveraging demo)
- [ ] Team CVs with certifications (Laravel/PHP expertise)
- [ ] Legal documents (trade license, VAT, tax clearance)
- [ ] Demo/prototype (link or screenshots)
- [ ] User manuals (English and Bengali) - existing, to be enhanced
- [ ] 1 hard copy in bound format
- [ ] 1 soft copy in USB drive
- [ ] All documents in PDF format
- [ ] Proper file naming convention

#### Documents Required Checklist

- [ ] Cover Letter
- [ ] Company Profile
- [ ] Technical Proposal (with demo assessment)
- [ ] Commercial Proposal
- [ ] Implementation Plan
- [ ] Team CVs
- [ ] Trade License
- [ ] VAT Registration Certificate
- [ ] Income Tax Clearance Certificate
- [ ] Bank Solvency Certificate
- [ ] Demo/Prototype (optional)

---

### TIMELINE

| Phase | Date | Activity |
|-------|------|----------|
| RFP Issuance | January 26, 2026 | RFP documents released to vendors |
| Queries Period | Jan 26 - Feb 5, 2026 | Vendors can submit queries |
| Demo Access | Feb 1 - Feb 5, 2026 | Qualified vendors access demo after NDA |
| Submission Deadline | February 15, 2026 (2:00 PM) | Last day for proposal submission |
| Evaluation Period | Feb 16 - Feb 28, 2026 | Committee evaluates all proposals |
| Vendor Selection | March 1, 2026 | Highest-evaluated vendor selected |
| Contract Negotiation | March 2-10, 2026 | Contract terms finalized |
| Contract Signing | March 15, 2026 | Agreement executed |
| Project Start | March 16, 2026 | Production upgrade begins |

**Project Duration:** 32 weeks (8 months) - Reduced from 37 weeks due to existing demo foundation

---

### EVALUATION PROCESS

1. **Initial Screening**
   - Verify eligibility criteria (5+ years experience, BDT 5 Crore turnover, etc.)
   - Check completeness of submission
   - Validate all legal documents
   - Ensure NDA signed for demo access

2. **Technical Evaluation**
   - Review technical proposal against requirements
   - Assess vendor's understanding of existing demo system
   - Evaluate upgrade strategy (leverage existing vs. rebuild)
   - Assess technology stack appropriateness
   - Evaluate security measures
   - Verify scalability and performance approach
   - Assess monitoring and logging implementation

3. **Commercial Evaluation**
   - Compare pricing across all proposals
   - Assess value for money (considering existing demo foundation)
   - Evaluate total cost of ownership
   - Review payment terms

4. **Team and Company Evaluation**
   - Review team composition and qualifications
   - Assess Laravel/PHP expertise (critical for this project)
   - Evaluate company experience in financial sector
   - Check client references and testimonials
   - Verify financial stability

5. **Presentation (Optional)**
   - Shortlisted vendors may be invited for presentation
   - Demo of proposed upgrade approach
   - Q&A session with evaluation committee
   - Assessment of vendor's understanding of demo system

6. **Final Selection**
   - Calculate weighted scores using evaluation scorecard
   - Rank all proposals
   - Select highest-evaluated responsive bidder
   - Obtain management approval

7. **Award Notification**
   - Notify selected vendor
   - Notify unsuccessful vendors
   - Begin contract negotiations

---

### KEY CONTACTS

#### For Technical Queries
- **Email:** technical@ucbl.com
- **Phone:** [Technical Contact Number]
- **Note:** Questions about current demo system welcome

#### For Commercial Queries
- **Email:** commercial@ucbl.com
- **Phone:** [Commercial Contact Number]

#### For Demo Access
- **Email:** procurement@ucbl.com
- **Phone:** +880-2-XXXXXXX
- **Note:** Demo access provided after NDA signing

#### For General Inquiries
- **Email:** procurement@ucbl.com
- **Phone:** +880-2-XXXXXXX
- **Address:** UCBL Tower, 29-30 Dilkusha C/A, Dhaka-1000, Bangladesh

---

### IMPORTANT NOTES

#### For Vendors
1. **Project Type:** This is a demo-to-production upgrade, not a greenfield project
2. **Proposal Validity:** 90 days from submission date
3. **Price Escalation:** Must be justified in proposal
4. **IP Rights:** All developed IP will belong to UCBL
5. **Source Code:** Must be provided upon completion (including all upgrades)
6. **Compliance:** Must comply with Bangladesh Cyber Security Act 2023
7. **Language:** All documentation in English and Bengali
8. **Support:** 24/7 support required for first 6 months
9. **Warranty:** 12-month warranty for software defects
10. **Timeline:** 32 weeks (8 months) - leverage existing demo foundation

#### For UCBL
1. **Demo System:** Fully functional demo with core features implemented
2. **Technology Stack:** Modern stack (PHP 8.4, Laravel 12, Reverb)
3. **No Obligation:** UCBL is not bound to accept lowest proposal
4. **Right to Reject:** UCBL reserves right to accept or reject any or all proposals
5. **Confidentiality:** All vendor proposals and demo access are confidential
6. **Transparency:** Evaluation process must be fair and transparent
7. **Documentation:** All evaluation decisions must be documented
8. **Conflict of Interest:** Any conflict must be disclosed immediately

---

### FILE ORGANIZATION

```
RFQ_tender/
├── README.md (this file)
├── VMSUCBL_RFP_Document.md (main RFP)
├── 01_Evaluation_Scorecard.md (evaluation rubric)
├── 02_Non_Disclosure_Agreement.md (NDA)
├── 03_Technical_Specifications.md (technical specs)
└── 04_Contract_Agreement.md (contract draft)
```

---

### DOCUMENT VERSIONING

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | January 26, 2026 | Initial RFP package creation (greenfield project) |
| 2.0 | January 26, 2026 | Updated for demo-to-production upgrade project |

---

### DISCLAIMER

This documentation is prepared for the exclusive use of United Commercial Bank Limited (UCBL) for the purpose of procuring production upgrade services for the existing Visitor Management System. All materials are confidential and proprietary to UCBL.

Unauthorized reproduction, distribution, or use of this documentation is strictly prohibited without prior written consent from UCBL.

---

**END OF PACKAGE DOCUMENTATION**

---

*For updates, clarifications, or amendments to this RFP, please visit: www.ucbl.com/tenders*

*Last Updated: January 26, 2026*
