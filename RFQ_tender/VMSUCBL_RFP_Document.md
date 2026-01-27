# REQUEST FOR PROPOSAL (RFP)
## Visitor Management System for United Commercial Bank Limited (UCBL)

---

**Reference No:** VMS/UCBL/RFP/2026/001  
**Date:** January 26, 2026  
**Closing Date:** February 15, 2026  
**Closing Time:** 2:00 PM (Bangladesh Standard Time)

---

### 1. INTRODUCTION

#### 1.1 Organization Overview
United Commercial Bank Limited (UCBL) is one of the leading private sector commercial banks in Bangladesh, committed to providing modern banking services with advanced technological solutions.

#### 1.2 Project Overview
UCBL has developed a comprehensive **Visitor Management System (VMS) Demo** that is currently operational and ready for production deployment. The system includes core functionalities with a modern technology stack. UCBL now invites proposals from eligible software development firms to **upgrade the demo to full production deployment** and implement additional advanced features across all UCBL branches and administrative offices throughout Bangladesh.

**Current Demo System Status:**
- Fully functional demo with core VMS features
- Modern technology stack implemented (PHP 8.4, Laravel 12, Reverb/WebSockets)
- Real-time features operational (Live Dashboard, Notifications)
- Production-ready architecture (Docker Swarm/Kubernetes ready)
- Security features implemented (RBAC, 2FA, Authentication/Authorization)
- Basic features working (RFID, CSV export, Email notifications)

#### 1.3 Purpose
This RFP aims to identify qualified vendors who can:

1. **Upgrade the existing demo to production deployment** for UCBL's 500+ branches
2. **Implement additional advanced features** (QR codes, Face Recognition, etc.)
3. **Scale the system** to support 10,000+ daily visitors
4. **Ensure production-grade reliability** with enterprise monitoring and logging
5. **Integrate with UCBL systems** (LDAP, HR, Email, SMS gateways)
6. **Provide ongoing maintenance and support** for long-term operations

The vendor will build upon the existing demo foundation to deliver a fully scalable, production-ready VMS that will:
- Streamline visitor registration and check-in/check-out processes
- Enhance security through face recognition, RFID, and QR code integration
- Provide real-time monitoring with Prometheus, Grafana, and Loki
- Ensure compliance with Bangladesh Bank regulations
- Improve overall visitor experience and operational efficiency

---

### 2. SCOPE OF WORK

#### 2.1 System Architecture
The proposed solution must be a web-based application with the following components:

**2.1.1 Frontend Module**
- Responsive web interface compatible with desktop, tablet, and mobile devices
- Support for major browsers (Chrome, Firefox, Safari, Edge)
- Modern UI/UX design following UCBL brand guidelines
- Multi-language support (English and Bengali)

**2.1.2 Backend Module**
- RESTful API architecture
- Secure data processing and storage
- Real-time notifications and alerts
- Integration with existing UCBL systems

**2.1.3 Database**
- Scalable database design (MySQL/PostgreSQL)
- Data backup and disaster recovery mechanisms
- Data encryption and security compliance

#### 2.2 Core Functional Requirements

**2.2.1 User Management**
- Role-based access control (RBAC) with following roles:
  - Administrator
  - Receptionist
  - Staff/Host
  - Visitor
- User authentication with multi-factor authentication (MFA)
- User profile management
- Permission management

**2.2.2 Visitor Registration**
**Current Demo Features:**
- Pre-registration via web portal (fully implemented)
- On-site registration at reception (fully implemented)
- Capturing visitor details:
  - Full name (implemented)
  - Contact information (phone, email) (implemented)
  - Company/organization (implemented)
  - Purpose of visit (implemented)
  - Photo capture (implemented)
  - ID document scanning (to be enhanced)
- Email and SMS OTP verification (fully implemented - 2FA complete)
- Visitor history tracking (implemented)

**Production Upgrade Requirements:**
- Enhance ID document scanning with OCR
- Improve photo capture with auto-crop and quality check
- Advanced visitor pre-approval workflow
- Bulk visitor registration for groups
- Visitor blacklist/whitelist management

**2.2.3 Visit Management**
- Appointment scheduling
- Host assignment and approval workflow
- Visit type classification:
  - Business meetings
  - Interviews
  - Deliveries
  - Service requests
  - VIP visits
- Multi-day visit support
- Visit status tracking:
  - Pending OTP verification
  - Pending host approval
  - Approved
  - Checked-in
  - Checked-out
  - Cancelled/Rejected

**2.2.4 Check-In/Check-Out System**
**Current Demo Features:**
- Quick check-in process (< 2 minutes) (implemented)
- RFID card generation and assignment (fully implemented)
- Automated check-out reminders (to be enhanced)
- Visitor badge printing (basic implementation)

**Production Upgrade Requirements:**
- Implement face recognition integration for verification (future)
- QR code-based check-in as alternative to RFID (new feature)
- Enhanced check-out reminder system with SMS/Email
- Multi-badge printing support (visitor + host badge)
- Self-service kiosk mode for check-in
- Automated visitor badge photo printing

**2.2.5 Security Features**
- Face recognition technology
- RFID card management
- OTP-based visitor verification
- Visitor blocking system
- Security audit logs
- Real-time alerts for unauthorized access

**2.2.6 Notification System**
**Current Demo Features:**
- Email notifications (fully implemented)
- SMS notifications for 2FA and alerts (fully implemented)
- Real-time notifications via WebSockets (implemented - Reverb)
- Multi-channel notification support (basic implementation)
- Auto email to Host with visitor list (implemented)

**Production Upgrade Requirements:**
- Enhanced SMS gateway integration with all Bangladeshi mobile operators
- Push notifications for mobile app (if implemented)
- WhatsApp Business API integration (optional)
- Customizable notification templates
- Notification scheduling and batching
- Real-time notification delivery tracking
- Multi-language notifications (English & Bengali)

**2.2.7 Reporting and Analytics**
**Current Demo Features:**
- Live Visitor Dashboard (fully implemented with real-time updates)
- Real-time visitor tracking via WebSockets (implemented)
- Custom report generation (basic implementation)
- Export to CSV for visitor lists (fully implemented)
- Daily/weekly/monthly statistics (basic implementation)

**Production Upgrade Requirements:**
- Advanced analytics with Grafana dashboards (Prometheus integration)
- Export to PDF and Excel (enhance from CSV only)
- Scheduled automated reports (email delivery)
- Compliance reports for Bangladesh Bank
- Custom report builder
- Branch-wise comparative analytics
- Peak hour analysis
- Visitor flow heatmap
- Automated compliance reports generation

**2.2.8 Integration Requirements**
**Current Demo Features:**
- Email server integration for notifications (implemented)
- SMS gateway integration for 2FA (basic implementation)

**Production Upgrade Requirements:**
- Integration with UCBL LDAP/Active Directory (new)
- HR system integration for staff/Host data synchronization (new)
- Enhanced SMS gateway integration with all Bangladeshi operators
- Email server integration optimization (enhance existing)
- CCTV system integration for visitor photos (optional)
- Access control system integration (optional)
- Bangladesh Bank compliance reporting integration (new)

#### 2.3 Non-Functional Requirements

**2.3.1 Performance**
- System response time: < 2 seconds for normal operations
- Support for 1,000+ concurrent users
- 99.9% system uptime availability

**2.3.2 Security**
- End-to-end encryption (TLS 1.3)
- Data encryption at rest (AES-256)
- Compliance with Bangladesh Cyber Security Act
- Regular security audits and penetration testing
- GDPR-like data protection for visitor data

**2.3.3 Scalability**
- Horizontal scaling capability
- Load balancing support
- Database sharding support for future growth
- Support for 500+ UCBL branches

**2.3.4 Reliability**
- Automatic failover mechanisms
- Data backup every 6 hours
- 30-day data retention
- Disaster recovery plan with RTO < 4 hours

**2.3.5 Usability**
- Intuitive user interface
- Training documentation and user manuals
- Online help and support
- Responsive design for all devices

---

### 3. TECHNICAL SPECIFICATIONS

#### 3.1 Current Technology Stack (Demo System)
The following technology stack is currently implemented in the demo system and will be used as the foundation for production deployment:

**3.1.1 Backend Framework**
- **PHP 8.4** - Latest stable version with performance optimizations
- **Laravel 12** - Modern PHP framework with advanced features
- **Reverb** - Laravel's WebSocket server for real-time features
- **Real-time WebSockets** - For live dashboard and instant notifications

**3.1.2 Web Server & Infrastructure**
- **Nginx** - High-performance web server and reverse proxy
- **Docker Swarm / Kubernetes** - Container orchestration for production deployment
- **Ubuntu Server** - Linux-based server operating system
- **Redis** - In-memory data store for caching and session management

**3.1.3 Database**
- **MySQL 8.0+** OR **PostgreSQL 13+** (vendor to recommend optimal choice)
- Database optimization and indexing for performance
- Connection pooling and query optimization

**3.1.4 Monitoring & Logging**
- **Prometheus** - Metrics collection and monitoring
- **Grafana** - Visualization and dashboards for system metrics
- **Loki** - Log aggregation and centralized logging

**3.1.5 Frontend**
- Modern JavaScript framework (React, Vue.js, or Angular) - Currently implemented with Laravel Blade + Alpine.js
- Tailwind CSS - Utility-first CSS framework
- Responsive design for all devices
- Real-time updates via WebSockets

#### 3.2 Technology Requirements for Production Upgrade
The vendor must:

1. **Maintain compatibility** with existing Laravel 12 codebase
2. **Enhance the existing stack** with production-grade optimizations
3. **Implement container orchestration** using Docker Swarm or Kubernetes
4. **Set up enterprise monitoring** with Prometheus, Grafana, and Loki
5. **Optimize database** for 10,000+ daily visitors across 500+ branches
6. **Ensure high availability** with proper failover and load balancing

**3.3 Proposed Additional Technologies (Optional)**
Vendor may recommend additional technologies for enhanced functionality:

- **QR Code Generation** - For alternative visitor check-in method
- **Face Recognition Integration** - Using computer vision libraries (OpenCV, FaceNet, etc.)
- **Advanced Caching** - Additional Redis layers or alternative solutions
- **CDN Integration** - For static assets across Bangladesh
- **Load Balancers** - HAProxy, Nginx Plus, or cloud-based solutions

#### 3.2 Face Recognition Requirements
- Accuracy: > 95%
- Processing time: < 1 second per face
- Support for multiple faces per visitor
- Liveness detection
- Database of 100,000+ faces
- Integration with standard cameras (1080p+)

#### 3.3 RFID System
- RFID card type: Mifare Classic 1K or equivalent
- Read range: 5-10 cm
- Card life: 5+ years
- Support for 50,000+ cards
- Secure card encoding

---

### 4. DELIVERABLES

#### 4.1 Software Deliverables
- Complete source code with documentation
- Database schema and migration scripts
- API documentation (Swagger/OpenAPI)
- Installation and deployment guide
- System administrator manual
- End-user manual in English and Bengali

#### 4.2 Hardware Deliverables (if applicable)
- RFID cards (500 units initial)
- Face recognition cameras (as per requirements)
- Card readers
- Badge printers
- Server specifications

#### 4.3 Training Deliverables
- Administrator training (2 days)
- Receptionist training (1 day per branch)
- Staff/Host training (online modules)
- Training videos
- Knowledge base

#### 4.4 Support Deliverables
- 24/7 technical support for first 6 months
- Bug fixes and patches
- Minor enhancements
- Performance optimization
- Security updates

---

### 5. PROJECT TIMELINE

| Phase | Duration | Deliverables |
|-------|-----------|--------------|
| Phase 1: Demo Assessment | 1 week | Demo system evaluation and gap analysis |
| Phase 2: Production Architecture Design | 2 weeks | Production-ready architecture with Docker/K8s, monitoring setup |
| Phase 3: Core Feature Upgrade | 6 weeks | Enhanced core features (QR codes, advanced reporting, etc.) |
| Phase 4: Integration & Optimization | 4 weeks | LDAP/HR integration, Prometheus/Grafana/Loki setup |
| Phase 5: Face Recognition Implementation | 4 weeks | Face detection and verification system |
| Phase 6: Testing & QA | 3 weeks | Comprehensive testing including performance and security |
| Phase 7: Pilot Implementation | 3 weeks | 10 branches pilot deployment |
| Phase 8: Full Rollout | 6 weeks | All 500+ branches deployment |
| Phase 9: Training & Handover | 3 weeks | Complete handover with documentation |

**Total Project Duration:** 32 weeks (approximately 8 months)

**Note:** Timeline is reduced from 37 to 32 weeks due to existing demo foundation. Vendor should leverage implemented features to accelerate deployment.

---

### 6. ELIGIBILITY CRITERIA

#### 6.1 Company Requirements
- Registered software development firm in Bangladesh
- Minimum 5 years of experience in enterprise software development
- Minimum 20 full-time employees
- Valid Trade License
- VAT Registration Certificate
- Income Tax Clearance Certificate
- Minimum annual turnover: BDT 5 Crore

#### 6.2 Experience Requirements
- Must have completed at least 3 similar projects for financial institutions
- Must have experience in implementing systems with 100+ concurrent users
- Must have face recognition integration experience
- Must have RFID system integration experience

#### 6.3 Team Requirements
- Project Manager with PMP certification (minimum 5 years experience)
- Senior Developers (minimum 3 years experience each)
- UI/UX Designer with portfolio
- QA Engineer with automation testing experience
- DevOps Engineer
- Security Specialist (optional but preferred)

---

### 7. PROPOSAL REQUIREMENTS

#### 7.1 Pricing Structure
Vendors must provide detailed pricing for:

**7.1.1 One-time Costs**
- Software development cost
- License fees (if any)
- Hardware costs (if included)
- Implementation cost
- Training cost
- Data migration cost

**7.1.2 Recurring Costs**
- Annual maintenance and support (Year 1, 2, 3)
- Cloud infrastructure costs (monthly/annual)
- Software updates and upgrades
- Additional RFID cards
- Technical support packages

**7.1.3 Optional Costs**
- Mobile application (iOS, Android)
- Advanced analytics module
- AI-powered insights
- Integration with third-party systems

#### 7.2 Currency and Payment Terms
- All prices must be in Bangladeshi Taka (BDT)
- Payment terms:
  - 20% advance upon contract signing
  - 30% upon alpha version delivery
  - 30% upon beta version delivery
  - 15% upon successful pilot completion
  - 15% upon full rollout and acceptance
- Payment within 30 days of invoice

#### 7.3 Validity
- Proposal validity: 90 days from submission date
- Price escalation clause (if applicable)

---

### 8. EVALUATION CRITERIA

The evaluation will be based on the following criteria:

| Criteria | Weightage |
|----------|------------|
| Technical Solution | 30% |
| Company Experience | 20% |
| Proposed Team Qualifications | 15% |
| Pricing | 20% |
| Implementation Timeline | 10% |
| Post-Sales Support | 5% |
| **Total** | **100%** |

#### 8.1 Technical Solution (30%)
- Completeness of proposed solution
- Technology stack appropriateness
- Security measures
- Scalability and performance
- Innovation and uniqueness

#### 8.2 Company Experience (20%)
- Similar projects completed
- Client testimonials
- Industry recognition
- Financial stability

#### 8.3 Team Qualifications (15%)
- Team composition
- Relevant certifications
- Domain expertise
- Communication skills

#### 8.4 Pricing (20%)
- Competitiveness of pricing
- Value for money
- Cost breakdown clarity
- Total cost of ownership

#### 8.5 Implementation Timeline (10%)
- Realistic timeline
- Project management methodology
- Risk mitigation
- Milestone achievement

#### 8.6 Post-Sales Support (5%)
- Support availability
- Response time commitments
- SLA provisions
- Training quality

---

### 9. SUBMISSION REQUIREMENTS

#### 9.1 Required Documents
Vendors must submit the following documents:

1. **Cover Letter** (on company letterhead)
2. **Company Profile** with:
   - Company registration details
   - Organizational structure
   - Financial statements (last 3 years)
   - Client list with references
   - Case studies of similar projects

3. **Technical Proposal** including:
   - System architecture diagram
   - Technology stack justification
   - Database design overview
   - Security architecture
   - Integration approach
   - Risk assessment and mitigation

4. **Commercial Proposal** including:
   - Detailed pricing breakdown
   - Payment terms
   - Warranty and support details
   - License terms (if applicable)

5. **Implementation Plan** with:
   - Detailed timeline with milestones
   - Project methodology
   - Resource allocation
   - Quality assurance plan

6. **Team CVs** with:
   - Education and certifications
   - Relevant experience
   - Role in project

7. **Legal Documents**:
   - Trade License
   - VAT Registration
   - Income Tax Clearance
   - Bank Solvency Certificate

8. **Demo/Prototype** (optional but recommended):
   - Link to live demo
   - Screenshots or video walkthrough
   - User manual draft

#### 9.2 Submission Format
- 1 hard copy in bound format
- 1 soft copy in USB drive
- PDF format for all documents
- File naming convention: "VMS_UCBL_[CompanyName]_[DocumentName]"

#### 9.3 Submission Address
**To:**
Procurement Department
United Commercial Bank Limited
Head Office
UCBL Tower, 29-30 Dilkusha C/A
Dhaka-1000, Bangladesh

**Attention:** Deputy Managing Director (Operations)

---

### 10. TERMS AND CONDITIONS

#### 10.1 Contract Terms
- Contract will be awarded to the highest-evaluated responsive bidder
- UCBL reserves the right to accept or reject any or all proposals
- UCBL is not bound to accept the lowest proposal
- No negotiations will be entertained after submission

#### 10.2 Intellectual Property Rights
- All source code, documentation, and IP will belong to UCBL
- Vendor must provide full source code upon completion
- No third-party license restrictions
- Warranty for IP infringement

#### 10.3 Confidentiality
- All information provided by UCBL is confidential
- Vendor must sign NDA before detailed discussions
- Data protection as per Bangladesh law

#### 10.4 Warranty
- 12-month warranty for software defects
- Free bug fixes during warranty period
- Warranty for hardware (if provided)

#### 10.5 Liability
- Vendor liable for data breaches or security lapses
- Indemnification for third-party claims
- Professional liability insurance required (minimum BDT 1 Crore)

#### 10.6 Termination
- UCBL can terminate for breach of contract
- 30-day notice period
- Payment for work completed

#### 10.7 Dispute Resolution
- Amicable settlement first
- Arbitration under Bangladesh Arbitration Act 2001
- Venue: Dhaka, Bangladesh

---

### 11. CONTACT INFORMATION

For any queries related to this RFP:

**Technical Queries:**
- Name: [Technical Contact Name]
- Email: [technical@ucbl.com]
- Phone: [Technical Contact Number]

**Commercial Queries:**
- Name: [Commercial Contact Name]
- Email: [commercial@ucbl.com]
- Phone: [Commercial Contact Number]

**Procurement Department:**
- Email: procurement@ucbl.com
- Phone: +880-2-XXXXXXX

---

### 12. APPENDICES

#### Appendix A: UCBL Branch Network
- Total branches: 500+
- Geographic coverage: All 64 districts
- Daily visitors: Estimated 10,000+

#### Appendix B: Regulatory Requirements
- Bangladesh Bank IT Security Guidelines
- Bangladesh Data Protection Act (draft)
- Bangladesh Cyber Security Act 2023

#### Appendix C: Technical Specifications
- Server specifications
- Network requirements
- Security requirements
- Backup requirements

#### Appendix D: Evaluation Scorecard
Detailed scoring rubric for evaluation committee

---

**END OF DOCUMENT**

---

*This RFP is a confidential document of United Commercial Bank Limited. Unauthorized reproduction or distribution is prohibited.*

*UCBL is an equal opportunity employer and encourages applications from all qualified vendors.*

*For the latest updates and clarifications, please visit: www.ucbl.com/tenders*
