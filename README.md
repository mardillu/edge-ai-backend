# Edge AI Backend

---

### Check out the Frontend
Checkout the frontend by [clicking here](https://github.com/mardillu/edge-ai-frontend)

### Demo
Ghanaians can test this out by dialing * 920*223# on their phones
Other users can use [this interactive demo](https://edge.mardillu.com/simulate)
or watch the recorded video at [https://edge.mardillu.com/field-test](https://edge.mardillu.com/field-test)

---

## Inspiration
Growing up in a resource-constrained community in a developing country, I realized the stark digital divide. While I was fortunate to receive an education, many, including my parents, did not. Their inability to speak English and lack of access to technologies like smartphones and the internet significantly limits their ability to benefit from advanced tools such as AI, which could dramatically improve daily tasks and access to information, like enhancing farming techniques to increase crop yields.

## What it does
EdgeAI is a USSD-based application designed to bridge the digital divide by enabling users, regardless of their access to smartphones or the internet, to interact with sophisticated AI models in their local languages. By simply typing a prompt in any local language on a basic feature phone, users receive AI-generated advice and answers instantly.

## How we built it
The architecture of EdgeAI consists of five main components:
1. **Client Device**: Typically a feature phone.
2. **Carrier/Network Operator**: Facilitates USSD requests and responses.
3. **USSD Vendor**: Manages the USSD code and serves as an intermediary by forwarding requests from the carrier to our backend and vice versa.
4. **Backend Application**: Developed in PHP, this acts as the nerve center, processing all incoming and outgoing requests.
5. **AI Service Integration (Gemini)**: Handles the language processing and response generation.

The user interaction begins when a user dials a USSD code, which is sent to their carrier. The carrier forwards this request to our USSD vendor, who then routes it to our backend application. Here, user commands are processed—inputs are first translated to English, responded to via Gemini, and then translated back into the user's language. Responses are paginated to fit within USSD's character limit before being sent back through the same chain to the user.

## Challenges we ran into
- **Character Limit**: USSD sessions limit messages to 160 characters. I implemented a pagination system to circumvent this.
- **Session Timeout**: USSD sessions time out after 60 seconds, requiring prompt user interaction.
- **Language Limitations**: Initial interactions are in English as detecting the user's language preemptively is challenging.
- **Operational Costs**: Funding the USSD code allocation for pilot testing was a financial challenge. In fact, by the time this submission is being reviewed, it is possible that my USSD subscription would have expired. I built a simple simulator to circumvent this [available here](https://edge.mardillu.com)
- **Literacy Requirement**: Users must be literate in some language to interact with the system.

## Accomplishments that we're proud of
EdgeAI has democratized access to advanced AI, enabling users without internet or English proficiency to obtain crucial information. Our pilot program with agricultural extension officers demonstrated significant improvements in delivering content to farmers, showcasing the practical utility of EdgeAI in real-world scenarios.

## What we learned
- **Technical Scalability**: Adapting complex AI responses to simple USSD interfaces taught us about the nuances of cross-technology communication.
- **User-Centric Design**: Designing for inclusivity means considering not just the technological but also the socio-economic barriers users face.
- **Language Processing**: Handling multiple languages deepened our understanding of natural language processing in non-English contexts.

## What's next for EdgeAI
The next frontier is to transcend the literacy barrier by incorporating voice recognition and response capabilities. Envisioning a service where users can interact with AI through voice calls in their local language could revolutionize access, making technology truly universal. This leap would transform EdgeAI from a helpful tool to a fundamental change agent in global technology access.
